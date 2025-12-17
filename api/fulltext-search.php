<?php
/**
 * Search API
 * Provides instant search by note title only
 */

header('Content-Type: application/json');
require __DIR__ . '/../bootstrap.php';

$query = trim($_GET['q'] ?? '');
$limit = min(max(1, (int)($_GET['limit'] ?? 10)), 50);
$offset = max(0, (int)($_GET['offset'] ?? 0));

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'error' => 'Query too short', 'results' => [], 'count' => 0]);
    exit;
}

try {
    // Title-only search (case-insensitive)
    $likeQuery = '%' . $query . '%';
    $exactQuery = $query;
    $startQuery = $query . '%';
    
    $stmt = $pdo->prepare("
        SELECT n.id, n.title, n.description, n.file_type, n.file_url,
               c.name AS category_name, u.username,
               CASE 
                   WHEN LOWER(n.title) = LOWER(:exact_query) THEN 100
                   WHEN LOWER(n.title) LIKE LOWER(:start_query) THEN 50
                   WHEN LOWER(n.title) LIKE LOWER(:like_query) THEN 25
                   ELSE 0
               END AS relevance,
               'title' AS match_type
        FROM notes n
        JOIN categories c ON c.id = n.category_id
        JOIN users u ON u.id = n.user_id
        WHERE n.status = 'published'
        AND LOWER(n.title) LIKE LOWER(:like_query)
        ORDER BY relevance DESC, n.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':like_query', $likeQuery);
    $stmt->bindValue(':exact_query', $exactQuery);
    $stmt->bindValue(':start_query', $startQuery);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results
    $formatted = array_map(function($row) use ($query) {
        $description = $row['description'] ?? '';
        $description = $description ? (mb_strlen($description) > 150 ? mb_substr($description, 0, 150) . '...' : $description) : 'No description';
        
        return [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $description,
            'file_type' => $row['file_type'],
            'category' => $row['category_name'],
            'author' => $row['username'],
            'relevance' => (float)($row['relevance'] ?? 0),
            'match_type' => $row['match_type'] ?? 'title',
            'url' => 'note-detail.php?id=' . $row['id']
        ];
    }, $results);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $formatted,
        'count' => count($formatted)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search error: ' . $e->getMessage()
    ]);
}

