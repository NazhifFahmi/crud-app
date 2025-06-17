<?php
require_once 'config/database.php';
$pageTitle = 'Employees - Employee Management System';

// Handle form submissions
if ($_POST) {
    try {
        // Validate required fields
        $required_fields = ['employee_id', 'first_name', 'last_name', 'email'];
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Validate email format
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Check for duplicate employee_id and email (except when updating same record)
        if (!empty($_POST['employee_id'])) {
            $sql = "SELECT id FROM employees WHERE employee_id = ?";
            $params = [$_POST['employee_id']];
            
            if (isset($_POST['update']) && !empty($_POST['id'])) {
                $sql .= " AND id != ?";
                $params[] = $_POST['id'];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $errors[] = "Employee ID already exists";
            }
        }
        
        if (!empty($_POST['email'])) {
            $sql = "SELECT id FROM employees WHERE email = ?";
            $params = [$_POST['email']];
            
            if (isset($_POST['update']) && !empty($_POST['id'])) {
                $sql .= " AND id != ?";
                $params[] = $_POST['id'];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $errors[] = "Email already exists";
            }
        }
        
        if (!empty($errors)) {
            $error = implode(", ", $errors);
        } else {
            if (isset($_POST['create'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO employees (employee_id, first_name, last_name, email, phone, position, department_id, salary, hire_date, address, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['employee_id'], $_POST['first_name'], $_POST['last_name'], 
                    $_POST['email'], $_POST['phone'], $_POST['position'], 
                    $_POST['department_id'] ?: null, $_POST['salary'] ?: null, $_POST['hire_date'] ?: null,
                    $_POST['address'], $_POST['status']
                ]);
                $success = "Employee created successfully!";
            }
            
            if (isset($_POST['update'])) {
                $stmt = $pdo->prepare("
                    UPDATE employees SET employee_id = ?, first_name = ?, last_name = ?, email = ?, phone = ?, 
                    position = ?, department_id = ?, salary = ?, hire_date = ?, address = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['employee_id'], $_POST['first_name'], $_POST['last_name'], 
                    $_POST['email'], $_POST['phone'], $_POST['position'], 
                    $_POST['department_id'] ?: null, $_POST['salary'] ?: null, $_POST['hire_date'] ?: null,
                    $_POST['address'], $_POST['status'], $_POST['id']
                ]);
                $success = "Employee updated successfully!";
            }
        }
        
        if (isset($_POST['delete'])) {
            // Check if employee has attendance records
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE employee_id = ?");
            $stmt->execute([$_POST['id']]);
            $attendanceCount = $stmt->fetch()['count'];
            
            if ($attendanceCount > 0) {
                // Don't delete, just deactivate
                $stmt = $pdo->prepare("UPDATE employees SET status = 'terminated' WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Employee deactivated successfully (has attendance records)!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Employee deleted successfully!";
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get edit employee data
$editEmployee = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editEmployee = $stmt->fetch();
}

// Get all employees with department info
$stmt = $pdo->query("
    SELECT e.*, d.name as department_name 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id = d.id 
    ORDER BY e.created_at DESC
");
$employees = $stmt->fetchAll();

// Get departments for dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2"></i>Employee Management
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal">
                <i class="fas fa-plus me-2"></i>Add Employee
            </button>
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

<!-- Search and Filter -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="search-input" placeholder="Search employees...">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="department-filter">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="status-filter">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="terminated">Terminated</option>
        </select>
    </div>
</div>

<!-- Employees Table -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Employee List (<?php echo count($employees); ?> total)
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Employee ID</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr class="searchable-item">
                            <td><?php echo $employee['id']; ?></td>
                            <td>
                                <img src="<?php echo $employee['profile_image'] ?: 'assets/images/default-avatar.png'; ?>" 
                                     alt="Profile" class="profile-img">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($employee['phone'] ?? 'No phone'); ?></small>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($employee['employee_id']); ?></span></td>
                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($employee['department_name'] ?? 'No Department'); ?></td>
                            <td>
                                <?php if ($employee['salary']): ?>
                                    <span class="fw-bold text-success">Rp <?php echo number_format($employee['salary'], 0, ',', '.'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge status-<?php echo $employee['status']; ?>">
                                    <?php echo ucfirst($employee['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="viewEmployee(<?php echo $employee['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                            onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $employee['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger btn-delete"
                                       data-name="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel">
                    <i class="fas fa-user-plus me-2"></i>
                    <span id="modal-title">Add New Employee</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="employee-id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employee ID *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="employee_id" id="employee_id" required>
                                    <button type="button" class="btn btn-outline-secondary" id="generate-emp-id">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Please provide a valid employee ID.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="terminated">Terminated</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" id="first_name" required>
                                <div class="invalid-feedback">Please provide a first name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" id="last_name" required>
                                <div class="invalid-feedback">Please provide a last name.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                                <div class="invalid-feedback">Please provide a valid email.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone" id="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" name="position" id="position">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Department</label>
                                <select class="form-select" name="department_id" id="department_id">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salary" class="form-label">Salary (IDR)</label>
                                <input type="number" class="form-control salary-input" name="salary" id="salary" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hire_date" class="form-label">Hire Date</label>
                                <input type="date" class="form-control" name="hire_date" id="hire_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" name="address" id="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create" id="submit-btn" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Employee Details Modal -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1" aria-labelledby="viewEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewEmployeeModalLabel">
                    <i class="fas fa-user me-2"></i>Employee Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="employee-details">
                <!-- Content will be loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
// Generate Employee ID
document.getElementById('generate-emp-id').addEventListener('click', function() {
    const currentYear = new Date().getFullYear();
    const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    const employeeId = `EMP${currentYear}${randomNum}`;
    document.getElementById('employee_id').value = employeeId;
});

// Format salary input with thousand separators
document.getElementById('salary').addEventListener('input', function() {
    let value = this.value.replace(/[^\d]/g, '');
    if (value) {
        this.value = parseInt(value).toLocaleString('id-ID');
    }
});

// Remove formatting when form is submitted
document.querySelector('form').addEventListener('submit', function() {
    const salaryInput = document.getElementById('salary');
    salaryInput.value = salaryInput.value.replace(/[^\d]/g, '');
});

function editEmployee(employee) {
    document.getElementById('modal-title').textContent = 'Edit Employee';
    document.getElementById('employee-id').value = employee.id;
    document.getElementById('employee_id').value = employee.employee_id;
    document.getElementById('first_name').value = employee.first_name;
    document.getElementById('last_name').value = employee.last_name;
    document.getElementById('email').value = employee.email;
    document.getElementById('phone').value = employee.phone || '';
    document.getElementById('position').value = employee.position || '';
    document.getElementById('department_id').value = employee.department_id || '';
    document.getElementById('salary').value = employee.salary || '';
    document.getElementById('hire_date').value = employee.hire_date || '';
    document.getElementById('address').value = employee.address || '';
    document.getElementById('status').value = employee.status;
    
    document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-2"></i>Update Employee';
    document.getElementById('submit-btn').name = 'update';
    
    new bootstrap.Modal(document.getElementById('employeeModal')).show();
}

function viewEmployee(employeeId) {
    // Fetch employee details via AJAX
    fetch(`api/employee-details.php?id=${employeeId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('employee-details').innerHTML = html;
            new bootstrap.Modal(document.getElementById('viewEmployeeModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('employee-details').innerHTML = '<p class="text-danger">Error loading employee details.</p>';
            new bootstrap.Modal(document.getElementById('viewEmployeeModal')).show();
        });
}

// Reset modal when closed
document.getElementById('employeeModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modal-title').textContent = 'Add New Employee';
    document.getElementById('employee-id').value = '';
    document.querySelector('form').reset();
    document.querySelector('form').classList.remove('was-validated');
    document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-2"></i>Save Employee';
    document.getElementById('submit-btn').name = 'create';
});
</script>

<?php include 'includes/footer.php'; ?>
