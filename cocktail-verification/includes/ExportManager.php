<?php
require_once __DIR__ . '/../config/database.php';

class ExportManager {
    private $pdo;
    private $exportDir;
    
    public function __construct() {
        $this->pdo = getDB();
        $this->exportDir = BASE_PATH . 'exports/';
        
        if (!file_exists($this->exportDir)) {
            mkdir($this->exportDir, 0777, true);
        }
    }
    
    /**
     * Generate export file
     */
    public function generateExport($format = 'csv', $includeUnverified = false, $includeExperts = true) {
        $filename = 'cocktail_verified_data_' . date('Y-m-d_H-i-s') . '.' . $format;
        $filepath = $this->exportDir . $filename;
        
        // Get all data
        $data = $this->getExportData($includeUnverified, $includeExperts);
        
        // Generate file based on format
        switch ($format) {
            case 'json':
                $this->generateJSON($filepath, $data);
                break;
            case 'csv':
            default:
                $this->generateCSV($filepath, $data);
                break;
        }
        
        return $filepath;
    }
    
    /**
     * Get all data for export
     */
    private function getExportData($includeUnverified, $includeExperts) {
        $data = [];
        
        // Get all cocktails with their verified tags
        $sql = "
            SELECT 
                c.*,
                cot.original_tags,
                GROUP_CONCAT(DISTINCT 
                    CASE 
                        WHEN ct.status = 'verified' OR ? = TRUE 
                        THEN CONCAT(t.tag_name, '|', ct.status, '|', 
                                   IFNULL(ct.confidence_score, 100), '|',
                                   IFNULL(e.username, 'system'), '|',
                                   IFNULL(DATE(ct.verified_at), ''))
                        ELSE NULL 
                    END
                    ORDER BY ct.status, ct.confidence_score DESC
                    SEPARATOR ';;'
                ) as tag_details
            FROM cocktails c
            LEFT JOIN cocktail_original_tags cot ON c.id = cot.cocktail_id
            LEFT JOIN cocktail_tags ct ON c.id = ct.cocktail_id
            LEFT JOIN tags t ON ct.tag_id = t.id
            LEFT JOIN experts e ON ct.verified_by = e.id
            WHERE ct.status = 'verified' OR ? = TRUE
            GROUP BY c.id
            ORDER BY c.strDrink
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$includeUnverified, $includeUnverified]);
        $cocktails = $stmt->fetchAll();
        
        // Process each cocktail
        foreach ($cocktails as $cocktail) {
            $cocktailData = $cocktail;
            
            // Parse tag details
            $tags = [];
            if (!empty($cocktail['tag_details'])) {
                $tagEntries = explode(';;', $cocktail['tag_details']);
                foreach ($tagEntries as $entry) {
                    if (!empty($entry)) {
                        $parts = explode('|', $entry);
                        $tags[] = [
                            'name' => $parts[0],
                            'status' => $parts[1],
                            'confidence' => $parts[2],
                            'verified_by' => $parts[3],
                            'verified_at' => $parts[4]
                        ];
                    }
                }
            }
            
            $cocktailData['tags'] = $tags;
            $data[] = $cocktailData;
        }
        
        return $data;
    }
    
    /**
     * Generate CSV export
     */
    private function generateCSV($filepath, $data) {
        if (empty($data)) {
            throw new Exception("No data to export");
        }
        
        $handle = fopen($filepath, 'w');
        
        // Build comprehensive headers
        $headers = [
            'idDrink', 'strDrink', 'strCategory', 'strAlcoholic', 'strGlass',
            'strInstructions', 'original_tags', 'verified_tags', 'pending_tags',
            'rejected_tags', 'total_tags_count', 'verified_tags_count',
            'avg_confidence_score', 'verification_status',
            'verified_by_experts', 'last_verified_at', 'verification_notes',
            'strDrinkThumb', 'strVideo'
        ];
        
        fputcsv($handle, $headers);
        
        // Write data rows with full details
        foreach ($data as $cocktail) {
            $verifiedExperts = [];
            $verifiedTagsList = [];
            $pendingTagsList = [];
            $rejectedTagsList = [];
            $totalConfidence = 0;
            $tagCount = 0;
            
            foreach ($cocktail['tags'] ?? [] as $tag) {
                if ($tag['status'] === 'verified') {
                    $verifiedTagsList[] = $tag['name'] . " ({$tag['confidence']}%)";
                    if (!in_array($tag['verified_by'], $verifiedExperts)) {
                        $verifiedExperts[] = $tag['verified_by'];
                    }
                    $totalConfidence += $tag['confidence'];
                    $tagCount++;
                } elseif ($tag['status'] === 'pending') {
                    $pendingTagsList[] = $tag['name'];
                } elseif ($tag['status'] === 'rejected') {
                    $rejectedTagsList[] = $tag['name'];
                }
            }
            
            $avgConfidence = $tagCount > 0 ? round($totalConfidence / $tagCount, 2) : 0;
            
            $csvRow = [
                $cocktail['idDrink'] ?? '',
                $cocktail['strDrink'] ?? '',
                $cocktail['strCategory'] ?? '',
                $cocktail['strAlcoholic'] ?? '',
                $cocktail['strGlass'] ?? '',
                $cocktail['strInstructions'] ?? '',
                $cocktail['original_tags'] ?? '',
                implode('; ', $verifiedTagsList),
                implode('; ', $pendingTagsList),
                implode('; ', $rejectedTagsList),
                count($cocktail['tags'] ?? []),
                $tagCount,
                $avgConfidence,
                $cocktail['verification_status'] ?? 'pending',
                implode(', ', $verifiedExperts),
                $cocktail['last_verified_at'] ?? '',
                '',
                $cocktail['strDrinkThumb'] ?? '',
                $cocktail['strVideo'] ?? ''
            ];
            
            fputcsv($handle, $csvRow);
        }
        
        fclose($handle);
    }
    
    /**
     * Generate JSON export
     */
    private function generateJSON($filepath, $data) {
        $jsonData = [
            'export_date' => date('Y-m-d H:i:s'),
            'total_cocktails' => count($data),
            'data' => $data
        ];
        
        file_put_contents($filepath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Get available exports
     */
    public function getAvailableExports() {
        $exports = [];
        
        if (is_dir($this->exportDir)) {
            $files = scandir($this->exportDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && !is_dir($this->exportDir . $file)) {
                    $exports[] = [
                        'filename' => $file,
                        'path' => $this->exportDir . $file,
                        'size' => filesize($this->exportDir . $file),
                        'modified' => date('Y-m-d H:i:s', filemtime($this->exportDir . $file))
                    ];
                }
            }
        }
        
        // Sort by modified date (newest first)
        usort($exports, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
        
        return $exports;
    }
    
    /**
     * Delete old exports (cleanup)
     */
    public function cleanupOldExports($days = 7) {
        $deleted = 0;
        $cutoff = time() - ($days * 24 * 60 * 60);
        
        if (is_dir($this->exportDir)) {
            $files = scandir($this->exportDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filepath = $this->exportDir . $file;
                    if (filemtime($filepath) < $cutoff) {
                        unlink($filepath);
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
}
?>