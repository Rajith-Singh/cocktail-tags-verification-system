<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/TagManager.php';

header('Content-Type: application/json');

try {
    requireLogin();
    
    $tagManager = new TagManager();
    $auth = new Auth();
    $expert = $auth->getCurrentExpert();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'search':
            $query = $_GET['q'] ?? '';
            $tags = $tagManager->searchTags($query, 50);
            echo json_encode(['success' => true, 'tags' => $tags]);
            break;
            
        case 'popular':
            $tags = $tagManager->getPopularTags(50);
            echo json_encode(['success' => true, 'tags' => $tags]);
            break;
            
        case 'categories':
            $categories = $tagManager->getTagCategories();
            echo json_encode(['success' => true, 'categories' => $categories]);
            break;
            
        case 'suggest':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("POST method required");
            }
            
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!validateCSRF($csrf_token)) {
                throw new Exception("Invalid CSRF token");
            }
            
            $tagName = trim($_POST['tag_name'] ?? '');
            $categoryId = $_POST['category_id'] ?? null;
            $description = trim($_POST['description'] ?? '');
            $rationale = trim($_POST['rationale'] ?? '');
            
            if (empty($tagName) || empty($rationale)) {
                throw new Exception("Tag name and rationale are required");
            }
            
            $suggestionId = $tagManager->suggestTag($tagName, $categoryId, $expert['id'], $description, $rationale);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tag suggestion submitted successfully',
                'suggestion_id' => $suggestionId
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
