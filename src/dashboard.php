<?php
require_once 'config/database.php';
$pageTitle = 'Dashboard - Employee Management System';

// Get statistics
try {
    // Total employees
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
    $totalEmployees = $stmt->fetch()['total'];

    // Total departments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM departments");
    $totalDepartments = $stmt->fetch()['total'];

    // Active projects
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM projects WHERE status IN ('planning', 'in_progress')");
    $activeProjects = $stmt->fetch()['total'];

    // Today's attendance
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM attendance WHERE date = CURDATE() AND status = 'present'");
    $todayAttendance = $stmt->fetch()['total'];

    // Recent employees
    $stmt = $pdo->query("
        SELECT e.*, d.name as department_name 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        WHERE e.status = 'active'
        ORDER BY e.created_at DESC 
        LIMIT 5
    ");
    $recentEmployees = $stmt->fetchAll();

    // Upcoming project deadlines
    $stmt = $pdo->query("
        SELECT * FROM projects 
        WHERE end_date >= CURDATE() 
        AND status IN ('planning', 'in_progress')
        ORDER BY end_date ASC 
        LIMIT 5
    ");
    $upcomingDeadlines = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </h1>
            <div class="text-end">
                <div id="current-time" class="fw-bold text-primary"></div>
                <div id="current-date" class="text-muted small"></div>
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

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo number_format($totalEmployees ?? 0); ?></div>
                    <div class="stat-label">Active Employees</div>
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
                    <div class="stat-number"><?php echo number_format($totalDepartments ?? 0); ?></div>
                    <div class="stat-label">Departments</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-sitemap"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo number_format($activeProjects ?? 0); ?></div>
                    <div class="stat-label">Active Projects</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo number_format($todayAttendance ?? 0); ?></div>
                    <div class="stat-label">Present Today</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Recent Data -->
<div class="row">
    <!-- Department Distribution Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Employee Distribution by Department
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Attendance Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Monthly Attendance Trend
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Employees -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Recent Employees
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentEmployees)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentEmployees as $employee): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $employee['profile_image'] ?: 'assets/images/default-avatar.png'; ?>" 
                                         alt="Profile" class="profile-img me-3">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></small><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($employee['department_name'] ?? 'No Department'); ?></small>
                                    </div>
                                </div>
                                <span class="badge status-<?php echo $employee['status']; ?>">
                                    <?php echo ucfirst($employee['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="employees.php" class="btn btn-outline-primary">View All Employees</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No employees found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Project Deadlines -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Upcoming Deadlines
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($upcomingDeadlines)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingDeadlines as $project): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($project['name']); ?></h6>
                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Due: <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo $project['status'] === 'planning' ? 'secondary' : 
                                             ($project['status'] === 'in_progress' ? 'primary' : 'success'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="projects.php" class="btn btn-outline-warning">View All Projects</a>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No upcoming deadlines.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <a href="employees.php?action=add" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>
                            Add Employee
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <a href="departments.php?action=add" class="btn btn-success w-100">
                            <i class="fas fa-plus me-2"></i>
                            Add Department
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <a href="projects.php?action=add" class="btn btn-warning w-100">
                            <i class="fas fa-project-diagram me-2"></i>
                            New Project
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <a href="reports.php" class="btn btn-info w-100">
                            <i class="fas fa-chart-bar me-2"></i>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast container for notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;"></div>

<?php include 'includes/footer.php'; ?>
