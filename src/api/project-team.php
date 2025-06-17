<?php
require_once '../config/database.php';

if (!isset($_GET['project_id'])) {
    echo '<p class="text-danger">Project ID not provided.</p>';
    exit;
}

$projectId = $_GET['project_id'];

try {
    // Get project details
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    if (!$project) {
        echo '<p class="text-danger">Project not found.</p>';
        exit;
    }

    // Get assigned employees
    $stmt = $pdo->prepare("
        SELECT e.id, e.employee_id, e.first_name, e.last_name, e.position, 
               d.name as department_name, ep.role, ep.assigned_date
        FROM employee_projects ep
        JOIN employees e ON ep.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE ep.project_id = ?
        ORDER BY ep.assigned_date DESC
    ");
    $stmt->execute([$projectId]);
    $assignedEmployees = $stmt->fetchAll();

    // Get available employees (not assigned to this project)
    $stmt = $pdo->prepare("
        SELECT e.id, e.employee_id, e.first_name, e.last_name, e.position, d.name as department_name
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        WHERE e.status = 'active' 
        AND e.id NOT IN (
            SELECT employee_id FROM employee_projects WHERE project_id = ?
        )
        ORDER BY e.first_name, e.last_name
    ");
    $stmt->execute([$projectId]);
    $availableEmployees = $stmt->fetchAll();

} catch(PDOException $e) {
    echo '<p class="text-danger">Database error: ' . $e->getMessage() . '</p>';
    exit;
}
?>

<div class="mb-3">
    <h6 class="text-primary">
        <i class="fas fa-project-diagram me-2"></i>
        <?php echo htmlspecialchars($project['name']); ?>
    </h6>
    <p class="text-muted mb-0"><?php echo htmlspecialchars($project['description']); ?></p>
</div>

<hr>

<!-- Assign New Employee -->
<div class="mb-4">
    <h6><i class="fas fa-user-plus me-2"></i>Assign Employee</h6>
    <form id="assignForm" class="row g-3">
        <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
        <div class="col-md-6">
            <select class="form-select" name="employee_id" required>
                <option value="">Select Employee</option>
                <?php foreach ($availableEmployees as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>">
                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="role" placeholder="Role (e.g., Developer, Analyst)" required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-plus"></i> Assign
            </button>
        </div>
    </form>
</div>

<!-- Current Team -->
<div>
    <h6><i class="fas fa-users me-2"></i>Current Team (<?php echo count($assignedEmployees); ?>)</h6>
    
    <?php if (count($assignedEmployees) > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Assigned Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignedEmployees as $emp): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($emp['employee_id']); ?> • 
                                    <?php echo htmlspecialchars($emp['position'] ?: 'No position'); ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($emp['department_name'] ?: 'No department'); ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($emp['role'] ?: 'Member'); ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($emp['assigned_date'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="unassignEmployee(<?php echo $emp['id']; ?>, <?php echo $projectId; ?>, '<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-3">
            <i class="fas fa-users fa-2x text-muted mb-2"></i>
            <p class="text-muted">No team members assigned yet.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Handle assign form submission
document.getElementById('assignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('assign_employee', '1');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        // Reload team management interface
        manageTeam(<?php echo $projectId; ?>);
        // Show success message
        Swal.fire('Success!', 'Employee assigned successfully!', 'success');
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error!', 'An error occurred. Please try again.', 'error');
    });
});

function unassignEmployee(employeeId, projectId, employeeName) {
    Swal.fire({
        title: 'Remove from team?',
        text: `Remove ${employeeName} from this project?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('unassign_employee', '1');
            formData.append('employee_id', employeeId);
            formData.append('project_id', projectId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Reload team management interface
                manageTeam(projectId);
                // Show success message
                Swal.fire('Success!', 'Employee removed from project!', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'An error occurred. Please try again.', 'error');
            });
        }
    });
}
</script>
