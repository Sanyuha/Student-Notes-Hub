<?php
// API endpoint to get rating statistics for a note
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
ob_start();

$noteId = (int)($_GET['note_id'] ?? 0);

if ($noteId === 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid note ID']);
    exit;
}

try {
    // Check if ratings table exists, if not return empty stats
    $ratingsStmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_ratings,
            AVG(rating) as average_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
        FROM ratings
        WHERE note_id = ?
    ');
    $ratingsStmt->execute([$noteId]);
    $stats = $ratingsStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats || $stats['total_ratings'] == 0) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'total_ratings' => 0,
            'average_rating' => 0,
            'distribution' => [
                '5' => 0,
                '4' => 0,
                '3' => 0,
                '2' => 0,
                '1' => 0
            ]
        ]);
        exit;
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'total_ratings' => (int)$stats['total_ratings'],
        'average_rating' => round((float)$stats['average_rating'], 2),
        'distribution' => [
            '5' => (int)$stats['five_star'],
            '4' => (int)$stats['four_star'],
            '3' => (int)$stats['three_star'],
            '2' => (int)$stats['two_star'],
            '1' => (int)$stats['one_star']
        ]
    ]);
    
} catch (Exception $e) {
    // If table doesn't exist, return empty stats
    ob_clean();
    echo json_encode([
        'success' => true,
        'total_ratings' => 0,
        'average_rating' => 0,
        'distribution' => [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0
        ]
    ]);
}



