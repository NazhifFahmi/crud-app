<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get attendance data for the last 6 months
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
        FROM attendance 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month
    ");
    
    $results = $stmt->fetchAll();
    
    $months = [];
    $present = [];
    $absent = [];
    
    foreach ($results as $result) {
        $months[] = date('M Y', strtotime($result['month'] . '-01'));
        $present[] = (int)$result['present'];
        $absent[] = (int)$result['absent'];
    }
    
    echo json_encode([
        'months' => $months,
        'present' => $present,
        'absent' => $absent
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
