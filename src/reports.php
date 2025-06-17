<?php
require_once 'config/database.php';
$pageTitle = 'Reports - Employee Management System';

// Handle export requests
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $date = $_GET['date'] ?? date('Y-m-d');
    
    switch ($exportType) {
        case 'attendance':
            exportAttendanceReport($pdo, $date);
            break;
        case 'employees':
            exportEmployeeReport($pdo);
            break;
        case 'departments':
            exportDepartmentReport($pdo);
            break;
        case 'projects':
            exportProjectReport($pdo);
            break;
    }
    exit;
}

function exportAttendanceReport($pdo, $date) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . $date . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee ID', 'Name', 'Department', 'Check In', 'Check Out', 'Working Hours', 'Status', 'Notes']);
    
    $stmt = $pdo->prepare("
        SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as name, 
               d.name as department, a.check_in, a.check_out, a.status, a.notes
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
        WHERE e.status = 'active'
        ORDER BY e.first_name, e.last_name
    ");
    $stmt->execute([$date]);
    
    while ($row = $stmt->fetch()) {
        $workingHours = '';
        if ($row['check_in'] && $row['check_out']) {
            $hours = (strtotime($row['check_out']) - strtotime($row['check_in'])) / 3600;
            $workingHours = number_format($hours, 1) . ' hours';
        }
        
        fputcsv($output, [
            $row['employee_id'],
            $row['name'],
            $row['department'] ?: 'No Department',
            $row['check_in'] ?: 'Not checked in',
            $row['check_out'] ?: 'Not checked out',
            $workingHours,
            $row['status'] ?: 'Not marked',
            $row['notes'] ?: ''
        ]);
    }
    fclose($output);
}

function exportEmployeeReport($pdo) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="employee_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee ID', 'Name', 'Email', 'Phone', 'Position', 'Department', 'Salary', 'Hire Date', 'Status']);
    
    $stmt = $pdo->query("
        SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as name, 
               e.email, e.phone, e.position, d.name as department, 
               e.salary, e.hire_date, e.status
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        ORDER BY e.first_name, e.last_name
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['employee_id'],
            $row['name'],
            $row['email'],
            $row['phone'] ?: '',
            $row['position'] ?: '',
            $row['department'] ?: 'No Department',
            $row['salary'] ? 'Rp ' . number_format($row['salary'], 0, ',', '.') : '',
            $row['hire_date'] ?: '',
            $row['status']
        ]);
    }
    fclose($output);
}

function exportDepartmentReport($pdo) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="department_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Department', 'Manager', 'Employee Count', 'Average Salary', 'Total Salary']);
    
    $stmt = $pdo->query("
        SELECT d.name, CONCAT(m.first_name, ' ', m.last_name) as manager_name,
               COUNT(e.id) as employee_count, 
               AVG(e.salary) as avg_salary,
               SUM(e.salary) as total_salary
        FROM departments d
        LEFT JOIN employees m ON d.manager_id = m.id
        LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
        GROUP BY d.id, d.name
        ORDER BY d.name
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['name'],
            $row['manager_name'] ?: 'No Manager',
            $row['employee_count'],
            $row['avg_salary'] ? 'Rp ' . number_format($row['avg_salary'], 0, ',', '.') : '',
            $row['total_salary'] ? 'Rp ' . number_format($row['total_salary'], 0, ',', '.') : ''
        ]);
    }
    fclose($output);
}

function exportProjectReport($pdo) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="project_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Project Name', 'Status', 'Start Date', 'End Date', 'Budget', 'Team Size', 'Description']);
    
    $stmt = $pdo->query("
        SELECT p.name, p.status, p.start_date, p.end_date, p.budget, p.description,
               COUNT(ep.employee_id) as team_size
        FROM projects p
        LEFT JOIN employee_projects ep ON p.id = ep.project_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['name'],
            ucfirst(str_replace('_', ' ', $row['status'])),
            $row['start_date'] ?: '',
            $row['end_date'] ?: '',
            $row['budget'] ? 'Rp ' . number_format($row['budget'], 0, ',', '.') : '',
            $row['team_size'],
            $row['description'] ?: ''
        ]);
    }
    fclose($output);
}

// Get various statistics for reports
try {
    // Employee statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_employees,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_employees,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_employees,
            SUM(CASE WHEN status = 'terminated' THEN 1 ELSE 0 END) as terminated_employees
        FROM employees
    ");
    $employeeStats = $stmt->fetch();

    // Department statistics
    $stmt = $pdo->query("
        SELECT d.name, COUNT(e.id) as employee_count, AVG(e.salary) as avg_salary
        FROM departments d
        LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
        GROUP BY d.id, d.name
        ORDER BY employee_count DESC
    ");
    $departmentStats = $stmt->fetchAll();

    // Project statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_projects,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_projects,
            SUM(budget) as total_budget
        FROM projects
    ");
    $projectStats = $stmt->fetch();

    // Recent attendance summary
    $stmt = $pdo->query("
        SELECT 
            DATE(date) as attendance_date,
            COUNT(*) as total_marked,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
        FROM attendance 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(date)
        ORDER BY attendance_date DESC
    ");
    $attendanceStats = $stmt->fetchAll();

    // Salary analysis
    $stmt = $pdo->query("
        SELECT 
            MIN(salary) as min_salary,
            MAX(salary) as max_salary,
            AVG(salary) as avg_salary,
            SUM(salary) as total_salary_cost
        FROM employees 
        WHERE status = 'active' AND salary IS NOT NULL
    ");
    $salaryStats = $stmt->fetch();

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
            </h1>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-2"></i>Export Reports
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportReport('employees')">
                        <i class="fas fa-users me-2"></i>Employee Report</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportReport('departments')">
                        <i class="fas fa-sitemap me-2"></i>Department Report</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportReport('projects')">
                        <i class="fas fa-project-diagram me-2"></i>Project Report</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportReport('attendance')">
                        <i class="fas fa-clock me-2"></i>Attendance Report</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo number_format($employeeStats['total_employees'] ?? 0); ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo number_format($projectStats['total_projects'] ?? 0); ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number">Rp <?php echo number_format(($salaryStats['total_salary_cost'] ?? 0) / 1000000, 1); ?>M</div>
                    <div class="stat-label">Monthly Salary Cost</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number">Rp <?php echo number_format(($projectStats['total_budget'] ?? 0) / 1000000, 1); ?>M</div>
                    <div class="stat-label">Project Budget</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Employee Status Distribution -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Employee Status Distribution
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="employeeStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Department Size Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Department Employee Count
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Reports -->
<div class="row">
    <!-- Department Analysis -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-sitemap me-2"></i>
                    Department Analysis
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Employees</th>
                                <th>Average Salary</th>
                                <th>Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departmentStats as $dept): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $dept['employee_count']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($dept['avg_salary']): ?>
                                            <span class="text-success fw-bold">
                                                Rp <?php echo number_format($dept['avg_salary'], 0, ',', '.'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($dept['avg_salary'] && $dept['employee_count']): ?>
                                            <span class="text-primary fw-bold">
                                                Rp <?php echo number_format($dept['avg_salary'] * $dept['employee_count'], 0, ',', '.'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Quick Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Active Employees</span>
                        <strong class="text-success"><?php echo $employeeStats['active_employees']; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Inactive Employees</span>
                        <strong class="text-warning"><?php echo $employeeStats['inactive_employees']; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Terminated</span>
                        <strong class="text-danger"><?php echo $employeeStats['terminated_employees']; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Completed Projects</span>
                        <strong class="text-success"><?php echo $projectStats['completed_projects']; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Active Projects</span>
                        <strong class="text-primary"><?php echo $projectStats['in_progress_projects']; ?></strong>
                    </div>
                </div>
                
                <hr>
                
                <h6 class="card-subtitle mb-2">Salary Insights</h6>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Minimum Salary</span>
                        <strong>Rp <?php echo number_format($salaryStats['min_salary'] ?? 0, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Maximum Salary</span>
                        <strong>Rp <?php echo number_format($salaryStats['max_salary'] ?? 0, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Average Salary</span>
                        <strong>Rp <?php echo number_format($salaryStats['avg_salary'] ?? 0, 0, ',', '.'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Attendance Summary -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-week me-2"></i>
                    Recent Attendance Summary (Last 7 Days)
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($attendanceStats)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Marked</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Attendance Rate</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceStats as $attendance): ?>
                                    <?php 
                                    $attendanceRate = $attendance['total_marked'] > 0 ? 
                                                     ($attendance['present_count'] / $attendance['total_marked']) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo date('M d, Y', strtotime($attendance['attendance_date'])); ?></strong></td>
                                        <td><span class="badge bg-info"><?php echo $attendance['total_marked']; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo $attendance['present_count']; ?></span></td>
                                        <td><span class="badge bg-danger"><?php echo $attendance['absent_count']; ?></span></td>
                                        <td><span class="badge bg-warning"><?php echo $attendance['late_count']; ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?php echo $attendanceRate >= 90 ? 'success' : ($attendanceRate >= 75 ? 'warning' : 'danger'); ?>" 
                                                     style="width: <?php echo $attendanceRate; ?>%">
                                                    <?php echo number_format($attendanceRate, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($attendanceRate >= 90): ?>
                                                <span class="badge bg-success">Excellent</span>
                                            <?php elseif ($attendanceRate >= 75): ?>
                                                <span class="badge bg-warning">Good</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Needs Attention</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No attendance data available for the last 7 days.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Employee Status Chart
const employeeStatusCtx = document.getElementById('employeeStatusChart').getContext('2d');
new Chart(employeeStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Inactive', 'Terminated'],
        datasets: [{
            data: [
                <?php echo $employeeStats['active_employees']; ?>,
                <?php echo $employeeStats['inactive_employees']; ?>,
                <?php echo $employeeStats['terminated_employees']; ?>
            ],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Department Chart
const departmentCtx = document.getElementById('departmentChart').getContext('2d');
new Chart(departmentCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo '"' . implode('","', array_column($departmentStats, 'name')) . '"'; ?>],
        datasets: [{
            label: 'Employee Count',
            data: [<?php echo implode(',', array_column($departmentStats, 'employee_count')); ?>],
            backgroundColor: '#007bff',
            borderColor: '#0056b3',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function exportReport(type) {
    window.open(`export.php?type=${type}`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>
