<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class TagManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Verify a tag
     */
    public function verifyTag($cocktailTagId, $expertId, $confidence = 100, $notes = '') {
        $this->pdo->beginTransaction();
        
        try {
            // Get current tag info
            $stmt = $this->pdo->prepare("
                SELECT ct.*, t.tag_name, c.id as cocktail_id, c.strDrink
                FROM cocktail_tags ct
                JOIN tags t ON ct.tag_id = t.id
                JOIN cocktails c ON ct.cocktail_id = c.id
                WHERE ct.id = ?
            ");
            
            $stmt->execute([$cocktailTagId]);
            $tag = $stmt->fetch();
            
            if (!$tag) {
                throw new Exception("Tag not found");
            }
            
            // Update tag status
            $updateStmt = $this->pdo->prepare("
                UPDATE cocktail_tags 
                SET status = 'verified', 
                    verified_by = ?,
                    verified_at = NOW(),
                    confidence_score = ?,
                    verification_notes = CONCAT_WS('\n', verification_notes, ?),
                    verification_count = verification_count + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([$expertId, $confidence, $notes, $cocktailTagId]);
            
            // Log the action
            $this->logVerification($expertId, $tag['cocktail_id'], $tag['tag_id'], 
                                  ACTION_VERIFY, $confidence, $notes);
            
            // Update tag usage count
            $this->updateTagUsage($tag['tag_id']);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Tag '{$tag['tag_name']}' verified for '{$tag['strDrink']}'"
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Reject a tag
     */
    public function rejectTag($cocktailTagId, $expertId, $reason = 'incorrect', $customReason = '', $notes = '') {
        $this->pdo->beginTransaction();
        
        try {
            // Get current tag info
            $stmt = $this->pdo->prepare("
                SELECT ct.*, t.tag_name, c.id as cocktail_id, c.strDrink
                FROM cocktail_tags ct
                JOIN tags t ON ct.tag_id = t.id
                JOIN cocktails c ON ct.cocktail_id = c.id
                WHERE ct.id = ?
            ");
            
            $stmt->execute([$cocktailTagId]);
            $tag = $stmt->fetch();
            
            if (!$tag) {
                throw new Exception("Tag not found");
            }
            
            // Update tag status
            $updateStmt = $this->pdo->prepare("
                UPDATE cocktail_tags 
                SET status = 'rejected', 
                    verified_by = ?,
                    verified_at = NOW(),
                    verification_notes = CONCAT_WS('\n', verification_notes, ?),
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $fullNotes = "Reason: $reason" . ($customReason ? " - $customReason" : "") . "\n$notes";
            $updateStmt->execute([$expertId, $fullNotes, $cocktailTagId]);
            
            // Log the action
            $this->logVerification($expertId, $tag['cocktail_id'], $tag['tag_id'], 
                                  ACTION_REJECT, 0, $fullNotes);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Tag '{$tag['tag_name']}' rejected from '{$tag['strDrink']}'"
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Add new tag to cocktail
     */
    public function addTagToCocktail($cocktailId, $tagName, $expertId, $categoryId = null, $confidence = 100) {
        $this->pdo->beginTransaction();
        
        try {
            // Get or create tag
            $tagId = $this->getOrCreateTag($tagName, $categoryId, $expertId);
            
            // Check if tag already linked to cocktail
            $checkStmt = $this->pdo->prepare("
                SELECT id FROM cocktail_tags 
                WHERE cocktail_id = ? AND tag_id = ?
            ");
            
            $checkStmt->execute([$cocktailId, $tagId]);
            
            if ($checkStmt->fetch()) {
                throw new Exception("Tag already exists for this cocktail");
            }
            
            // Link tag to cocktail
            $insertStmt = $this->pdo->prepare("
                INSERT INTO cocktail_tags (cocktail_id, tag_id, source, verified_by, 
                                          verified_at, confidence_score, status, created_at)
                VALUES (?, ?, 'expert_added', ?, NOW(), ?, 'verified', NOW())
            ");
            
            $insertStmt->execute([$cocktailId, $tagId, $expertId, $confidence]);
            $cocktailTagId = $this->pdo->lastInsertId();
            
            // Log the action
            $this->logVerification($expertId, $cocktailId, $tagId, 
                                  ACTION_ADD, $confidence, "Added tag: $tagName");
            
            // Get cocktail name for response
            $cocktailStmt = $this->pdo->prepare("SELECT strDrink FROM cocktails WHERE id = ?");
            $cocktailStmt->execute([$cocktailId]);
            $cocktail = $cocktailStmt->fetch();
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'cocktail_tag_id' => $cocktailTagId,
                'message' => "Tag '$tagName' added to '{$cocktail['strDrink']}'"
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Remove tag from cocktail
     */
    public function removeTagFromCocktail($cocktailTagId, $expertId, $reason = '') {
        $this->pdo->beginTransaction();
        
        try {
            // Get tag info before removal
            $stmt = $this->pdo->prepare("
                SELECT ct.*, t.tag_name, c.id as cocktail_id, c.strDrink
                FROM cocktail_tags ct
                JOIN tags t ON ct.tag_id = t.id
                JOIN cocktails c ON ct.cocktail_id = c.id
                WHERE ct.id = ?
            ");
            
            $stmt->execute([$cocktailTagId]);
            $tag = $stmt->fetch();
            
            if (!$tag) {
                throw new Exception("Tag not found");
            }
            
            // Log before deletion
            $this->logVerification($expertId, $tag['cocktail_id'], $tag['tag_id'], 
                                  ACTION_REMOVE, 0, "Removed tag. Reason: $reason");
            
            // Delete the tag relationship
            $deleteStmt = $this->pdo->prepare("DELETE FROM cocktail_tags WHERE id = ?");
            $deleteStmt->execute([$cocktailTagId]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Tag '{$tag['tag_name']}' removed from '{$tag['strDrink']}'"
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get or create tag
     */
    private function getOrCreateTag($tagName, $categoryId, $expertId) {
        // Clean tag name
        $tagName = trim($tagName);
        
        // Check if tag exists
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE tag_name = ?");
        $stmt->execute([$tagName]);
        $tag = $stmt->fetch();
        
        if ($tag) {
            return $tag['id'];
        }
        
        // Create new tag
        $slug = $this->generateSlug($tagName);
        
        $insertStmt = $this->pdo->prepare("
            INSERT INTO tags (tag_name, slug, category_id, created_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $insertStmt->execute([$tagName, $slug, $categoryId, $expertId]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Generate slug from tag name
     */
    private function generateSlug($tagName) {
        $slug = strtolower($tagName);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = trim($slug, '-');
        
        if (empty($slug)) {
            $slug = 'tag-' . substr(md5($tagName), 0, 8);
        }
        
        return $slug;
    }
    
    /**
     * Update tag usage count
     */
    private function updateTagUsage($tagId) {
        $stmt = $this->pdo->prepare("
            UPDATE tags t
            SET usage_count = (
                SELECT COUNT(*) FROM cocktail_tags 
                WHERE tag_id = ? AND status = 'verified'
            ),
            verified_usage_count = (
                SELECT COUNT(*) FROM cocktail_tags 
                WHERE tag_id = ? AND status = 'verified'
            ),
            updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$tagId, $tagId, $tagId]);
    }
    
    /**
     * Log verification action
     */
    private function logVerification($expertId, $cocktailId, $tagId, $action, $confidence, $notes) {
        $stmt = $this->pdo->prepare("
            INSERT INTO verification_logs (expert_id, cocktail_id, tag_id, 
                                          action_type, old_value, new_value, notes, performed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $oldValue = '';
        $newValue = $action . (($action === ACTION_VERIFY) ? " (confidence: $confidence%)" : '');
        
        $stmt->execute([$expertId, $cocktailId, $tagId, $action, $oldValue, $newValue, $notes]);
    }
    
    /**
     * Get all tag categories
     */
    public function getTagCategories() {
        $stmt = $this->pdo->query("
            SELECT * FROM tag_categories 
            WHERE is_active = 1 
            ORDER BY display_order, name
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get popular tags
     */
    public function getPopularTags($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT t.*, tc.name as category_name, tc.color_code,
                   COUNT(ct.id) as usage_count
            FROM tags t
            LEFT JOIN tag_categories tc ON t.category_id = tc.id
            LEFT JOIN cocktail_tags ct ON t.id = ct.tag_id AND ct.status = 'verified'
            GROUP BY t.id
            ORDER BY usage_count DESC, t.tag_name
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search tags
     */
    public function searchTags($query, $limit = 20) {
        $searchTerm = "%$query%";
        
        $stmt = $this->pdo->prepare("
            SELECT t.*, tc.name as category_name, tc.color_code
            FROM tags t
            LEFT JOIN tag_categories tc ON t.category_id = tc.id
            WHERE t.tag_name LIKE ? OR t.slug LIKE ?
            ORDER BY t.usage_count DESC, t.tag_name
            LIMIT ?
        ");
        
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Suggest new tag
     */
    public function suggestTag($tagName, $categoryId, $expertId, $description = '', $rationale = '') {
        // Check if suggestion already exists
        $stmt = $this->pdo->prepare("
            SELECT id FROM tag_suggestions 
            WHERE tag_name = ? AND status = 'pending'
        ");
        
        $stmt->execute([$tagName]);
        
        if ($stmt->fetch()) {
            throw new Exception("This tag suggestion is already pending review");
        }
        
        // Check if tag already exists
        $tagStmt = $this->pdo->prepare("SELECT id FROM tags WHERE tag_name = ?");
        $tagStmt->execute([$tagName]);
        
        if ($tagStmt->fetch()) {
            throw new Exception("This tag already exists in the system");
        }
        
        // Create suggestion
        $slug = $this->generateSlug($tagName);
        
        $insertStmt = $this->pdo->prepare("
            INSERT INTO tag_suggestions (tag_name, slug, category_id, description, 
                                        rationale, suggested_by, suggested_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $insertStmt->execute([$tagName, $slug, $categoryId, $description, $rationale, $expertId]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Check if tag exists
     */
    public function tagExists($tagName) {
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE tag_name = ? OR slug = ?");
        $stmt->execute([$tagName, $this->generateSlug($tagName)]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get tag by ID
     */
    public function getTagById($tagId) {
        $stmt = $this->pdo->prepare("
            SELECT t.*, tc.name as category_name, tc.color_code
            FROM tags t
            LEFT JOIN tag_categories tc ON t.category_id = tc.id
            WHERE t.id = ?
        ");
        
        $stmt->execute([$tagId]);
        return $stmt->fetch();
    }
}
?>