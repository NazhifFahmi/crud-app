<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['employee_id']) || !isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$employeeId = $_POST['employee_id'];
$action = $_POST['action'];
$today = date('Y-m-d');
$currentTime = date('H:i:s');

try {
    // Check if attendance record exists for today
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->execute([$employeeId, $today]);
    $attendance = $stmt->fetch();

    if ($action === 'checkin') {
        if ($attendance) {
            // Update check-in time
            $stmt = $pdo->prepare("UPDATE attendance SET check_in = ?, status = 'present' WHERE employee_id = ? AND date = ?");
            $stmt->execute([$currentTime, $employeeId, $today]);
        } else {
            // Create new attendance record
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in, status) VALUES (?, ?, ?, 'present')");
            $stmt->execute([$employeeId, $today, $currentTime]);
        }
        
        // Determine if late (assuming work starts at 09:00)
        $status = $currentTime > '09:00:00' ? 'late' : 'present';
        $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE employee_id = ? AND date = ?");
        $stmt->execute([$status, $employeeId, $today]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully checked in at ' . date('H:i', strtotime($currentTime)),
            'time' => $currentTime,
            'status' => $status
        ]);
        
    } elseif ($action === 'checkout') {
        if ($attendance && $attendance['check_in']) {
            // Update check-out time
            $stmt = $pdo->prepare("UPDATE attendance SET check_out = ? WHERE employee_id = ? AND date = ?");
            $stmt->execute([$currentTime, $employeeId, $today]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Successfully checked out at ' . date('H:i', strtotime($currentTime)),
                'time' => $currentTime
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No check-in record found for today']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
