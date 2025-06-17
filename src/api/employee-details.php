<?php
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    echo '<p class="text-danger">Employee ID not provided.</p>';
    exit;
}

$employeeId = $_GET['id'];

try {
    // Get employee details with department info
    $stmt = $pdo->prepare("
        SELECT e.*, d.name as department_name,
               CONCAT(m.first_name, ' ', m.last_name) as manager_name
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN employees m ON d.manager_id = m.id
        WHERE e.id = ?
    ");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();

    if (!$employee) {
        echo '<p class="text-danger">Employee not found.</p>';
        exit;
    }

    // Get employee projects
    $stmt = $pdo->prepare("
        SELECT p.name as project_name, p.status as project_status, 
               ep.role, ep.assigned_date
        FROM employee_projects ep
        JOIN projects p ON ep.project_id = p.id
        WHERE ep.employee_id = ?
        ORDER BY ep.assigned_date DESC
    ");
    $stmt->execute([$employeeId]);
    $projects = $stmt->fetchAll();

    // Get recent attendance
    $stmt = $pdo->prepare("
        SELECT date, check_in, check_out, status, notes
        FROM attendance
        WHERE employee_id = ?
        ORDER BY date DESC
        LIMIT 10
    ");
    $stmt->execute([$employeeId]);
    $attendance = $stmt->fetchAll();

} catch(PDOException $e) {
    echo '<p class="text-danger">Database error: ' . $e->getMessage() . '</p>';
    exit;
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="text-center mb-4">
            <img src="<?php echo $employee['profile_image'] ?: '../assets/images/default-avatar.png'; ?>" 
                 alt="Profile" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
            <h4 class="mt-3"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h4>
            <p class="text-muted"><?php echo htmlspecialchars($employee['position'] ?: 'No Position'); ?></p>
            <span class="badge bg-<?php echo $employee['status'] === 'active' ? 'success' : ($employee['status'] === 'inactive' ? 'warning' : 'danger'); ?>">
                <?php echo ucfirst($employee['status']); ?>
            </span>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-id-badge me-2"></i>Employee Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Employee ID:</strong></td>
                        <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Phone:</strong></td>
                        <td><?php echo htmlspecialchars($employee['phone'] ?: 'Not provided'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Department:</strong></td>
                        <td><?php echo htmlspecialchars($employee['department_name'] ?: 'No Department'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Manager:</strong></td>
                        <td><?php echo htmlspecialchars($employee['manager_name'] ?: 'No Manager'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6">
                <h6><i class="fas fa-briefcase me-2"></i>Employment Details</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Hire Date:</strong></td>
                        <td><?php echo $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : 'Not set'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Salary:</strong></td>
                        <td>
                            <?php if ($employee['salary']): ?>
                                <span class="text-success fw-bold">Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></span>
                            <?php else: ?>
                                Not set
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Address:</strong></td>
                        <td><?php echo htmlspecialchars($employee['address'] ?: 'Not provided'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Joined:</strong></td>
                        <td><?php echo date('M d, Y', strtotime($employee['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Projects Section -->
        <div class="mt-4">
            <h6><i class="fas fa-tasks me-2"></i>Assigned Projects (<?php echo count($projects); ?>)</h6>
            <?php if (count($projects) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Assigned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['project_name']); ?></td>
                                    <td><?php echo htmlspecialchars($project['role'] ?: 'Member'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $project['project_status'] === 'completed' ? 'success' : 
                                                ($project['project_status'] === 'in_progress' ? 'primary' : 
                                                ($project['project_status'] === 'planning' ? 'info' : 'secondary'));
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $project['project_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($project['assigned_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No projects assigned</p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Attendance -->
        <div class="mt-4">
            <h6><i class="fas fa-calendar-check me-2"></i>Recent Attendance</h6>
            <?php if (count($attendance) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance as $att): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($att['date'])); ?></td>
                                    <td><?php echo $att['check_in'] ? date('H:i', strtotime($att['check_in'])) : '-'; ?></td>
                                    <td><?php echo $att['check_out'] ? date('H:i', strtotime($att['check_out'])) : '-'; ?></td>
                                    <td>
                                        <?php 
                                        if ($att['check_in'] && $att['check_out']) {
                                            $hours = (strtotime($att['check_out']) - strtotime($att['check_in'])) / 3600;
                                            echo number_format($hours, 1) . 'h';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $att['status'] === 'present' ? 'success' : 
                                                ($att['status'] === 'late' ? 'warning' : 
                                                ($att['status'] === 'half_day' ? 'info' : 'danger'));
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $att['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No attendance records</p>
            <?php endif; ?>
        </div>
    </div>
</div>
