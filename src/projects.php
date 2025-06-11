<?php
require_once 'config/database.php';
$pageTitle = 'Projects - Employee Management System';

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['create'])) {
            $stmt = $pdo->prepare("
                INSERT INTO projects (name, description, start_date, end_date, status, budget) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['name'], $_POST['description'], $_POST['start_date'], 
                $_POST['end_date'], $_POST['status'], $_POST['budget']
            ]);
            $success = "Project created successfully!";
        }
        
        if (isset($_POST['update'])) {
            $stmt = $pdo->prepare("
                UPDATE projects SET name = ?, description = ?, start_date = ?, end_date = ?, status = ?, budget = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'], $_POST['description'], $_POST['start_date'], 
                $_POST['end_date'], $_POST['status'], $_POST['budget'], $_POST['id']
            ]);
            $success = "Project updated successfully!";
        }
        
        if (isset($_POST['delete'])) {
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $success = "Project deleted successfully!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all projects with assigned employee count
$stmt = $pdo->query("
    SELECT p.*, COUNT(ep.employee_id) as assigned_employees
    FROM projects p 
    LEFT JOIN employee_projects ep ON p.id = ep.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$projects = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-project-diagram me-2"></i>Project Management
            </h1>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#projectModal">
                <i class="fas fa-plus me-2"></i>Add Project
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

<!-- Project Stats -->
<div class="row mb-4">
    <?php
    $statusCounts = array_count_values(array_column($projects, 'status'));
    $totalBudget = array_sum(array_column($projects, 'budget'));
    ?>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo count($projects); ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo $statusCounts['in_progress'] ?? 0; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-cogs"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number"><?php echo $statusCounts['planning'] ?? 0; ?></div>
                    <div class="stat-label">Planning</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stat-card info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number">Rp <?php echo number_format($totalBudget / 1000000, 1); ?>M</div>
                    <div class="stat-label">Total Budget</div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Search -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="search-input" placeholder="Search projects...">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="status-filter">
            <option value="">All Status</option>
            <option value="planning">Planning</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-outline-primary w-100" onclick="toggleView()">
            <i class="fas fa-list me-2" id="view-icon"></i>
            <span id="view-text">List View</span>
        </button>
    </div>
</div>

<!-- Projects Grid View -->
<div id="grid-view">
    <div class="row">
        <?php foreach ($projects as $project): ?>
            <div class="col-lg-4 col-md-6 mb-4 searchable-item">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?php 
                            echo $project['status'] === 'planning' ? 'secondary' : 
                                 ($project['status'] === 'in_progress' ? 'primary' : 
                                 ($project['status'] === 'completed' ? 'success' : 'danger')); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                        </span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="editProject(<?php echo htmlspecialchars(json_encode($project)); ?>)">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="manageTeam(<?php echo $project['id']; ?>)">
                                        <i class="fas fa-users me-2"></i>Manage Team
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['name']); ?>')">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($project['name']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?></p>
                        
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="border-end">
                                    <h6 class="text-primary mb-0"><?php echo $project['assigned_employees']; ?></h6>
                                    <small class="text-muted">Team Members</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h6 class="text-success mb-0">Rp <?php echo number_format($project['budget'] / 1000000, 1); ?>M</h6>
                                <small class="text-muted">Budget</small>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('M d, Y', strtotime($project['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                            </small>
                        </div>
                        
                        <!-- Progress bar (placeholder) -->
                        <div class="project-progress mb-2">
                            <div class="progress-bar bg-<?php 
                                echo $project['status'] === 'completed' ? 'success' : 
                                     ($project['status'] === 'in_progress' ? 'primary' : 'secondary'); 
                            ?>" style="width: <?php 
                                echo $project['status'] === 'completed' ? '100' : 
                                     ($project['status'] === 'in_progress' ? '60' : '10'); 
                            ?>%"></div>
                        </div>
                        <small class="text-muted">
                            Progress: <?php 
                                echo $project['status'] === 'completed' ? '100' : 
                                     ($project['status'] === 'in_progress' ? '60' : '10'); 
                            ?>%
                        </small>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Days left: <?php 
                                    $daysLeft = max(0, ceil((strtotime($project['end_date']) - time()) / 86400));
                                    echo $daysLeft;
                                ?>
                            </small>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewProjectDetails(<?php echo $project['id']; ?>)">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($projects)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Projects Found</h4>
                    <p class="text-muted">Start by creating your first project.</p>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#projectModal">
                        <i class="fas fa-plus me-2"></i>Add Project
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Projects Table View (hidden by default) -->
<div id="table-view" style="display: none;">
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Budget</th>
                            <th>Team Size</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($project['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 50)) . '...'; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $project['status'] === 'planning' ? 'secondary' : 
                                             ($project['status'] === 'in_progress' ? 'primary' : 
                                             ($project['status'] === 'completed' ? 'success' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($project['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($project['end_date'])); ?></td>
                                <td class="text-success fw-bold">Rp <?php echo number_format($project['budget'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $project['assigned_employees']; ?></span>
                                </td>
                                <td>
                                    <div class="project-progress">
                                        <div class="progress-bar bg-primary" style="width: <?php 
                                            echo $project['status'] === 'completed' ? '100' : 
                                                 ($project['status'] === 'in_progress' ? '60' : '10'); 
                                        ?>%"></div>
                                    </div>
                                    <small><?php 
                                        echo $project['status'] === 'completed' ? '100' : 
                                             ($project['status'] === 'in_progress' ? '60' : '10'); 
                                    ?>%</small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewProjectDetails(<?php echo $project['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="editProject(<?php echo htmlspecialchars(json_encode($project)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="manageTeam(<?php echo $project['id']; ?>)">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['name']); ?>')">
                                            <i class="fas fa-trash"></i>
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
</div>

<!-- Project Modal -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalLabel">
                    <i class="fas fa-plus me-2"></i>
                    <span id="modal-title">Add New Project</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="project-id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Project Name *</label>
                                <input type="text" class="form-control" name="name" id="name" required>
                                <div class="invalid-feedback">Please provide a project name.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="status" required>
                                    <option value="planning">Planning</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="4" 
                                  placeholder="Enter project description..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="budget" class="form-label">Budget (IDR)</label>
                        <input type="number" class="form-control" name="budget" id="budget" step="0.01">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create" id="submit-btn" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let isGridView = true;

function toggleView() {
    const gridView = document.getElementById('grid-view');
    const tableView = document.getElementById('table-view');
    const viewIcon = document.getElementById('view-icon');
    const viewText = document.getElementById('view-text');
    
    if (isGridView) {
        gridView.style.display = 'none';
        tableView.style.display = 'block';
        viewIcon.className = 'fas fa-th me-2';
        viewText.textContent = 'Grid View';
        isGridView = false;
    } else {
        gridView.style.display = 'block';
        tableView.style.display = 'none';
        viewIcon.className = 'fas fa-list me-2';
        viewText.textContent = 'List View';
        isGridView = true;
    }
}

function editProject(project) {
    document.getElementById('modal-title').textContent = 'Edit Project';
    document.getElementById('project-id').value = project.id;
    document.getElementById('name').value = project.name;
    document.getElementById('description').value = project.description || '';
    document.getElementById('start_date').value = project.start_date;
    document.getElementById('end_date').value = project.end_date;
    document.getElementById('status').value = project.status;
    document.getElementById('budget').value = project.budget || '';
    
    document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-2"></i>Update Project';
    document.getElementById('submit-btn').name = 'update';
    
    new bootstrap.Modal(document.getElementById('projectModal')).show();
}

function deleteProject(id, name) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete the project "${name}". This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="id" value="${id}">
                              <input type="hidden" name="delete" value="1">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function viewProjectDetails(projectId) {
    // Placeholder for project details view
    alert(`View details for project ID: ${projectId}`);
}

function manageTeam(projectId) {
    // Placeholder for team management
    alert(`Manage team for project ID: ${projectId}`);
}

// Reset modal when closed
document.getElementById('projectModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modal-title').textContent = 'Add New Project';
    document.getElementById('project-id').value = '';
    document.querySelector('form').reset();
    document.querySelector('form').classList.remove('was-validated');
    document.getElementById('submit-btn').innerHTML = '<i class="fas fa-save me-2"></i>Save Project';
    document.getElementById('submit-btn').name = 'create';
});
</script>

<?php include 'includes/footer.php'; ?>
