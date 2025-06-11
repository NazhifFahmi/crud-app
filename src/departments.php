<?php
require_once 'config/database.php';
$pageTitle = 'Departments - Employee Management System';

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['create'])) {
            $stmt = $pdo->prepare("INSERT INTO departments (name, description, manager_id) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['manager_id'] ?: null]);
            $success = "Department created successfully!";
        }
        
        if (isset($_POST['update'])) {
            $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ?, manager_id = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['manager_id'] ?: null, $_POST['id']]);
            $success = "Department updated successfully!";
        }
        
        if (isset($_POST['delete'])) {
            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $success = "Department deleted successfully!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all departments with employee count and manager info
$stmt = $pdo->query("
    SELECT d.*, 
           COUNT(e.id) as employee_count,
           CONCAT(m.first_name, ' ', m.last_name) as manager_name
    FROM departments d 
    LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
    LEFT JOIN employees m ON d.manager_id = m.id
    GROUP BY d.id
    ORDER BY d.name
");
$departments = $stmt->fetchAll();

// Get employees for manager dropdown
$stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM employees WHERE status = 'active' ORDER BY first_name");
$employees = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-sitemap me-2"></i>Department Management
            </h1>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#departmentModal">
                <i class="fas fa-plus me-2"></i>Add Department
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

<!-- Department Stats -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo count($departments); ?></div>
                    <div class="stat-label">Total Departments</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-sitemap"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo array_sum(array_column($departments, 'employee_count')); ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo count(array_filter($departments, function($d) { return $d['manager_name']; })); ?></div>
                    <div class="stat-label">With Managers</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo count(array_filter($departments, function($d) { return $d['employee_count'] == 0; })); ?></div>
                    <div class="stat-label">Empty Departments</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Departments Grid -->
<div class="row">
    <?php foreach ($departments as $department): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-building me-2"></i>
                            <?php echo htmlspecialchars($department['name']); ?>
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="editDepartment(<?php echo htmlspecialchars(json_encode($department)); ?>)">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteDepartment(<?php echo $department['id']; ?>, '<?php echo htmlspecialchars($department['name']); ?>')">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo htmlspecialchars($department['description'] ?: 'No description available.'); ?></p>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary mb-0"><?php echo $department['employee_count']; ?></h4>
                                <small class="text-muted">Employees</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="mb-0">Manager</h6>
                            <small class="text-muted">
                                <?php echo $department['manager_name'] ?: 'Not assigned'; ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Created: <?php echo date('M d, Y', strtotime($department['created_at'])); ?>
                        </small>
                        <a href="employees.php?department=<?php echo $department['id']; ?>" class="btn btn-sm btn-outline-primary">
                            View Employees
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($departments)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Departments Found</h4>
                <p class="text-muted">Start by creating your first department.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departmentModal">
                    <i class="fas fa-plus me-2"></i>Add Department
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Department Modal -->
<div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalLabel">
                    <i class="fas fa-plus me-2"></i>
                    <span id="modal-title">Add New Department</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="department-id">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Department Name *</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                        <div class="invalid-feedback">Please provide a department name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="4" 
                                  placeholder="Enter department description..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="manager_id" class="form-label">Department Manager</label>
                        <select class="form-select" name="manager_id" id="manager_id">
                            <option value="">Select Manager</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create" id="submit-btn" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Save Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editDepartment(department) {
    document.getElementById('modal-title').textContent = 'Edit Department';
    document.getElementById('department-id').value = department.id;
    document.getElementById('name').value = department.name;
    document.getElementById('description').value = department.description || '';
    document.getElementById('manager_id').value = department.manager_id || '';
    
    document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-2"></i>Update Department';
    document.getElementById('submit-btn').name = 'update';
    document.getElementById('submit-btn').className = 'btn btn-warning';
    
    new bootstrap.Modal(document.getElementById('departmentModal')).show();
}

function deleteDepartment(id, name) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete the department "${name}". This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="id" value="${id}">
                              <input type="hidden" name="delete" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Reset modal when closed
document.getElementById('departmentModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modal-title').textContent = 'Add New Department';
    document.getElementById('department-id').value = '';
    document.querySelector('form').reset();
    document.querySelector('form').classList.remove('was-validated');
    document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-2"></i>Save Department';
    document.getElementById('submit-btn').name = 'create';
    document.getElementById('submit-btn').className = 'btn btn-success';
});
</script>

<?php include 'includes/footer.php'; ?>
