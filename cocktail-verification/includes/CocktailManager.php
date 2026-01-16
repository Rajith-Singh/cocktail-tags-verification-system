<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class CocktailManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Get cocktail by ID with all related data
     */
    public function getCocktail($id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   cot.original_tags,
                   GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name) as current_tags
            FROM cocktails c
            LEFT JOIN cocktail_original_tags cot ON c.id = cot.cocktail_id
            LEFT JOIN cocktail_tags ct ON c.id = ct.cocktail_id AND ct.status = 'verified'
            LEFT JOIN tags t ON ct.tag_id = t.id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        
        $stmt->execute([$id]);
        $cocktail = $stmt->fetch();
        
        if ($cocktail) {
            $cocktail['ingredients'] = $this->getIngredients($id);
            $cocktail['verified_tags'] = $this->getVerifiedTags($id);
            $cocktail['pending_tags'] = $this->getPendingTags($id);
            $cocktail['all_tags'] = $this->getAllTags($id);
        }
        
        return $cocktail;
    }
    
    /**
     * Get ingredients for cocktail
     */
    private function getIngredients($cocktailId) {
        $ingredients = [];
        
        for ($i = 1; $i <= 15; $i++) {
            $stmt = $this->pdo->prepare("
                SELECT strIngredient{$i} as ingredient, strMeasure{$i} as measure 
                FROM cocktails 
                WHERE id = ? 
                AND strIngredient{$i} IS NOT NULL 
                AND strIngredient{$i} != ''
            ");
            
            $stmt->execute([$cocktailId]);
            $result = $stmt->fetch();
            
            if ($result && !empty($result['ingredient'])) {
                $ingredients[] = [
                    'ingredient' => $result['ingredient'],
                    'measure' => $result['measure'] ?? ''
                ];
            }
        }
        
        return $ingredients;
    }
    
    /**
     * Get verified tags for cocktail
     */
    private function getVerifiedTags($cocktailId) {
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.tag_name, tc.name as category, tc.color_code,
                   ct.confidence_score, ct.verified_by, ct.verified_at,
                   e.username as verifier_name, e.expertise_level
            FROM cocktail_tags ct
            JOIN tags t ON ct.tag_id = t.id
            LEFT JOIN tag_categories tc ON t.category_id = tc.id
            LEFT JOIN experts e ON ct.verified_by = e.id
            WHERE ct.cocktail_id = ? AND ct.status = 'verified'
            ORDER BY ct.confidence_score DESC, ct.verified_at DESC
        ");
        
        $stmt->execute([$cocktailId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get pending tags for cocktail
     */
    private function getPendingTags($cocktailId) {
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.tag_name, tc.name as category, tc.color_code,
                   ct.source, ct.created_at
            FROM cocktail_tags ct
            JOIN tags t ON ct.tag_id = t.id
            LEFT JOIN tag_categories tc ON t.category_id = tc.id
            WHERE ct.cocktail_id = ? AND ct.status = 'pending'
            ORDER BY ct.created_at DESC
        ");
        
        $stmt->execute([$cocktailId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all tags for cocktail
     */
    private function getAllTags($cocktailId) {
        $stmt = $this->pdo->prepare("
            SELECT t.id, t.tag_name, tc.name as category, tc.color_code,
                   ct.status, ct.confidence_score, ct.verified_at,
                   e.username as verifier_name
            FROM cocktail_tags ct
            JOIN tags t ON ct.tag_id = t.id
            LEFT JOIN tag_categories tc ON t.category_id = tc.id
            LEFT JOIN experts e ON ct.verified_by = e.id
            WHERE ct.cocktail_id = ?
            ORDER BY ct.status, ct.confidence_score DESC
        ");
        
        $stmt->execute([$cocktailId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get cocktails for verification
     */
    public function getCocktailsForVerification($expertId, $limit = 20, $offset = 0) {
        $sql = "
            SELECT c.id, c.idDrink, c.strDrink, c.strDrinkThumb, c.strCategory, 
                   c.strAlcoholic, c.strGlass, c.verification_status,
                   c.total_tags, c.verified_tags,
                   COUNT(DISTINCT ct.id) as pending_tags_count,
                   GROUP_CONCAT(DISTINCT t.tag_name) as pending_tag_names,
                   MAX(vq.assigned_at) as last_assigned
            FROM cocktails c
            LEFT JOIN cocktail_tags ct ON c.id = ct.cocktail_id AND ct.status = 'pending'
            LEFT JOIN tags t ON ct.tag_id = t.id
            LEFT JOIN verification_queue vq ON c.id = vq.cocktail_id AND vq.assigned_to = ?
            WHERE c.verification_status IN ('pending', 'partially_verified')
            GROUP BY c.id
            ORDER BY c.verified_tags ASC, c.total_tags DESC, last_assigned ASC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$expertId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get random cocktail for verification
     */
    public function getRandomCocktailForVerification($expertId) {
        $sql = "
            SELECT c.id, c.strDrink, c.strDrinkThumb, c.strCategory, 
                   c.strAlcoholic, c.strGlass, c.verification_status
            FROM cocktails c
            WHERE c.verification_status IN ('pending', 'partially_verified')
            AND NOT EXISTS (
                SELECT 1 FROM verification_logs vl 
                WHERE vl.cocktail_id = c.id 
                AND vl.expert_id = ? 
                AND vl.action_type IN ('verify_cocktail', 'dispute_cocktail')
            )
            ORDER BY RAND()
            LIMIT 1
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$expertId]);
        return $stmt->fetch();
    }
    
    /**
     * Get cocktail statistics
     */
    public function getCocktailStats() {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'partially_verified' => 0,
            'fully_verified' => 0,
            'total_tags' => 0,
            'verified_tags' => 0
        ];
        
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN verification_status = 'partially_verified' THEN 1 ELSE 0 END) as partially_verified,
                SUM(CASE WHEN verification_status = 'fully_verified' THEN 1 ELSE 0 END) as fully_verified,
                SUM(total_tags) as total_tags,
                SUM(verified_tags) as verified_tags
            FROM cocktails
        ");
        
        $result = $stmt->fetch();
        if ($result) {
            $stats = array_merge($stats, $result);
        }
        
        return $stats;
    }
    
    /**
     * Update cocktail verification status
     */
    public function updateVerificationStatus($cocktailId) {
        // Get total and verified tags
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified
            FROM cocktail_tags
            WHERE cocktail_id = ?
        ");
        
        $stmt->execute([$cocktailId]);
        $counts = $stmt->fetch();
        
        // Determine status
        $status = 'pending';
        if ($counts['total'] > 0) {
            if ($counts['verified'] == $counts['total']) {
                $status = 'fully_verified';
            } else {
                $status = 'partially_verified';
            }
        }
        
        // Update cocktail
        $updateStmt = $this->pdo->prepare("
            UPDATE cocktails 
            SET 
                verification_status = ?,
                total_tags = ?,
                verified_tags = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $updateStmt->execute([$status, $counts['total'], $counts['verified'], $cocktailId]);
    }
    
    /**
     * Search cocktails
     */
    public function searchCocktails($query, $limit = 50) {
        $searchTerm = "%$query%";
        
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.idDrink, c.strDrink, c.strDrinkThumb, c.strCategory, 
                   c.strAlcoholic, c.strGlass, c.verification_status,
                   c.total_tags, c.verified_tags
            FROM cocktails c
            WHERE c.strDrink LIKE ? 
               OR c.strCategory LIKE ? 
               OR c.strInstructions LIKE ?
            ORDER BY c.strDrink
            LIMIT ?
        ");
        
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get verification progress for expert
     */
    public function getExpertProgress($expertId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(DISTINCT cocktail_id) as cocktails_verified,
                COUNT(ct.id) as tags_verified,
                MAX(performed_at) as last_verification
            FROM verification_logs
            WHERE expert_id = ? AND action_type = 'verify'
        ");
        
        $stmt->execute([$expertId]);
        return $stmt->fetch();
    }
    
    /**
     * Get cocktails needing verification by expert
     */
    public function getCocktailsForExpert($expertId, $limit = 20, $offset = 0) {
        $sql = "
            SELECT c.id, c.idDrink, c.strDrink, c.strDrinkThumb, c.strCategory, 
                   c.strAlcoholic, c.strGlass, c.verification_status,
                   c.total_tags, c.verified_tags,
                   COUNT(DISTINCT ct.id) as pending_tags_count,
                   GROUP_CONCAT(DISTINCT t.tag_name) as pending_tag_names,
                   MAX(vq.assigned_at) as last_assigned
            FROM cocktails c
            LEFT JOIN cocktail_tags ct ON c.id = ct.cocktail_id AND ct.status = 'pending'
            LEFT JOIN tags t ON ct.tag_id = t.id
            LEFT JOIN verification_queue vq ON c.id = vq.cocktail_id AND vq.assigned_to = ?
            WHERE c.verification_status IN ('pending', 'partially_verified')
            GROUP BY c.id
            ORDER BY c.verified_tags ASC, c.total_tags DESC, last_assigned ASC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$expertId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get cocktails by status with pagination
     */
    public function getCocktailsByStatus($status, $limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.idDrink, c.strDrink, c.strDrinkThumb, c.strCategory, 
                   c.strAlcoholic, c.strGlass, c.verification_status,
                   c.total_tags, c.verified_tags,
                   COUNT(DISTINCT ct.id) as pending_tags_count
            FROM cocktails c
            LEFT JOIN cocktail_tags ct ON c.id = ct.cocktail_id AND ct.status = 'pending'
            WHERE c.verification_status = ?
            GROUP BY c.id
            ORDER BY c.updated_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$status, $limit, $offset]);
        return $stmt->fetchAll();
    }
}
?>