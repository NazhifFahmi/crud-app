<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get employee count by department
    $stmt = $pdo->query("
        SELECT d.name, COUNT(e.id) as count
        FROM departments d
        LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
        GROUP BY d.id, d.name
        ORDER BY count DESC
    ");
    
    $results = $stmt->fetchAll();
    
    $labels = [];
    $values = [];
    
    foreach ($results as $result) {
        $labels[] = $result['name'];
        $values[] = (int)$result['count'];
    }
    
    echo json_encode([
        'labels' => $labels,
        'values' => $values
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
