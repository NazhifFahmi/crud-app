<?php
require_once 'config/database.php';
$pageTitle = 'Attendance - Employee Management System';

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['mark_attendance'])) {
            $stmt = $pdo->prepare("
                INSERT INTO attendance (employee_id, date, check_in, check_out, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                check_out = VALUES(check_out), status = VALUES(status), notes = VALUES(notes)
            ");
            $stmt->execute([
                $_POST['employee_id'], $_POST['date'], $_POST['check_in'], 
                $_POST['check_out'], $_POST['status'], $_POST['notes']
            ]);
            $success = "Attendance marked successfully!";
        }
        
        if (isset($_POST['mark_all_present'])) {
            $date = $_POST['date'];
            // Get all active employees
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE status = 'active'");
            $stmt->execute();
            $allEmployees = $stmt->fetchAll();
            
            foreach ($allEmployees as $emp) {
                // Check if attendance already exists
                $stmt = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
                $stmt->execute([$emp['id'], $date]);
                $exists = $stmt->fetch();
                
                if (!$exists) {
                    // Insert new attendance record
                    $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in, status) VALUES (?, ?, '09:00:00', 'present')");
                    $stmt->execute([$emp['id'], $date]);
                }
            }
            $success = "All employees marked as present!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get today's date
$today = date('Y-m-d');
$selectedDate = $_GET['date'] ?? $today;

// Get all employees with today's attendance
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name,
           a.check_in, a.check_out, a.status as attendance_status, a.notes
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
    WHERE e.status = 'active'
    ORDER BY e.first_name, e.last_name
");
$stmt->execute([$selectedDate]);
$employees = $stmt->fetchAll();

// Get attendance statistics for selected date
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) as half_day
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
    WHERE e.status = 'active'
");
$stmt->execute([$selectedDate]);
$stats = $stmt->fetch();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-clock me-2"></i>Attendance Management
            </h1>
            <div class="d-flex gap-2">
                <input type="date" class="form-control" id="date-picker" value="<?php echo $selectedDate; ?>">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                    <i class="fas fa-plus me-2"></i>Mark Attendance
                </button>
            </div>
        </div>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Attendance Statistics -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo $stats['total_employees']; ?></div>
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
                    <div class="stat-number"><?php echo $stats['present']; ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo $stats['late']; ?></div>
                    <div class="stat-label">Late</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo $stats['absent']; ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Date Info -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-day me-2"></i>
                            Attendance for <?php echo date('l, F d, Y', strtotime($selectedDate)); ?>
                        </h5>
                        <p class="card-text text-muted mb-0">
                            Attendance Rate: 
                            <span class="fw-bold text-success">
                                <?php echo $stats['total_employees'] > 0 ? round(($stats['present'] / $stats['total_employees']) * 100, 1) : 0; ?>%
                            </span>
                        </p>
                    </div>
                    <div class="text-end">
                        <button class="btn btn-outline-success me-2" onclick="markAllPresent()">
                            <i class="fas fa-check-double me-1"></i>Mark All Present
                        </button>
                        <button class="btn btn-outline-primary" onclick="exportAttendance()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Table -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Employee Attendance
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Working Hours</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $employee['profile_image'] ?: 'assets/images/default-avatar.png'; ?>" 
                                         alt="Profile" class="profile-img me-3">
                                    <div>
                                        <strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($employee['employee_id']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($employee['department_name'] ?? 'No Department'); ?></td>
                            <td>
                                <?php if ($employee['check_in']): ?>
                                    <span class="badge bg-success"><?php echo date('H:i', strtotime($employee['check_in'])); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not checked in</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($employee['check_out']): ?>
                                    <span class="badge bg-info"><?php echo date('H:i', strtotime($employee['check_out'])); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not checked out</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($employee['check_in'] && $employee['check_out']) {
                                    $hours = (strtotime($employee['check_out']) - strtotime($employee['check_in'])) / 3600;
                                    echo '<span class="fw-bold">' . number_format($hours, 1) . ' hrs</span>';
                                } else {
                                    echo '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($employee['attendance_status']): ?>
                                    <span class="badge bg-<?php 
                                        echo $employee['attendance_status'] === 'present' ? 'success' :
                                             ($employee['attendance_status'] === 'late' ? 'warning' :
                                             ($employee['attendance_status'] === 'half_day' ? 'info' : 'danger'));
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $employee['attendance_status'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Not marked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($employee['notes'] ?: '-'); ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (!$employee['check_in']): ?>
                                        <button type="button" class="btn btn-sm btn-success btn-checkin" 
                                                data-employee-id="<?php echo $employee['id']; ?>" 
                                                data-action="checkin">
                                            <i class="fas fa-sign-in-alt"></i> In
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($employee['check_in'] && !$employee['check_out']): ?>
                                        <button type="button" class="btn btn-sm btn-info btn-checkin" 
                                                data-employee-id="<?php echo $employee['id']; ?>" 
                                                data-action="checkout">
                                            <i class="fas fa-sign-out-alt"></i> Out
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editAttendance(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', '<?php echo $employee['check_in'] ?? ''; ?>', '<?php echo $employee['check_out'] ?? ''; ?>', '<?php echo $employee['attendance_status'] ?? ''; ?>', '<?php echo htmlspecialchars($employee['notes'] ?? ''); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceModalLabel">
                    <i class="fas fa-clock me-2"></i>
                    <span id="modal-title">Mark Attendance</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
                    
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employee *</label>
                        <select class="form-select" name="employee_id" id="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select an employee.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="check_in" class="form-label">Check In Time</label>
                                <input type="time" class="form-control" name="check_in" id="check_in">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="check_out" class="form-label">Check Out Time</label>
                                <input type="time" class="form-control" name="check_out" id="check_out">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" 
                                  placeholder="Add any notes about the attendance..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="mark_attendance" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Date picker change event
document.getElementById('date-picker').addEventListener('change', function() {
    window.location.href = '?date=' + this.value;
});

// Check-in/Check-out functionality
document.addEventListener('DOMContentLoaded', function() {
    const checkinButtons = document.querySelectorAll('.btn-checkin');
    
    checkinButtons.forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.getAttribute('data-employee-id');
            const action = this.getAttribute('data-action');
            const employeeName = this.closest('tr').querySelector('td:nth-child(1) strong').textContent;
            
            Swal.fire({
                title: `${action === 'checkin' ? 'Check In' : 'Check Out'}`,
                text: `${action === 'checkin' ? 'Check in' : 'Check out'} ${employeeName}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: `Yes, ${action === 'checkin' ? 'Check In' : 'Check Out'}`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request
                    const formData = new FormData();
                    formData.append('employee_id', employeeId);
                    formData.append('action', action);
                    
                    fetch('api/attendance.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'An error occurred. Please try again.', 'error');
                    });
                }
            });
        });
    });
});

function editAttendance(employeeId, employeeName, checkIn, checkOut, status, notes) {
    document.getElementById('modal-title').textContent = `Edit Attendance - ${employeeName}`;
    document.getElementById('employee_id').value = employeeId;
    document.getElementById('check_in').value = checkIn;
    document.getElementById('check_out').value = checkOut;
    document.getElementById('status').value = status || 'present';
    document.getElementById('notes').value = notes;
    
    // Disable employee selection when editing
    document.getElementById('employee_id').disabled = true;
    
    new bootstrap.Modal(document.getElementById('attendanceModal')).show();
}

function markAllPresent() {
    Swal.fire({
        title: 'Mark All Present?',
        text: 'This will mark all employees as present for today. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, mark all',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const date = document.getElementById('date-picker').value;
            const formData = new FormData();
            formData.append('mark_all_present', '1');
            formData.append('date', date);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                Swal.fire('Success!', 'All employees marked as present.', 'success').then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'An error occurred. Please try again.', 'error');
            });
        }
    });
}

function exportAttendance() {
    const date = document.getElementById('date-picker').value;
    window.open(`reports.php?export=attendance&date=${date}`, '_blank');
}

// Reset modal when closed
document.getElementById('attendanceModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modal-title').textContent = 'Mark Attendance';
    document.getElementById('employee_id').disabled = false;
    document.querySelector('form').reset();
    document.querySelector('form').classList.remove('was-validated');
});
</script>

<?php include 'includes/footer.php'; ?>
