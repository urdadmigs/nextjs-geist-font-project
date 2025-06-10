<?php
require_once 'config.php';
require_once 'db.php';

// Check if user is logged in
requireLogin();

// Handle delete request
if (isset($_POST['delete_id']) && $_SESSION['role'] === 'admin') {
    try {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $_SESSION['success'] = "Employee deleted successfully.";
        header("Location: employees.php");
        exit();
    } catch (PDOException $e) {
        error_log("Delete Error: " . $e->getMessage());
        $_SESSION['error'] = "Error deleting employee.";
    }
}

// Fetch all employees
try {
    $stmt = $pdo->query("
        SELECT e.*, 
               (SELECT COUNT(*) FROM payroll p WHERE p.employee_id = e.id) as payroll_count
        FROM employees e 
        ORDER BY e.last_name, e.first_name
    ");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching employees.";
    $employees = [];
}

require_once 'header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Employees Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="add_employee.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Employee
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Job Title</th>
                        <th>Base Salary</th>
                        <th>Payroll Records</th>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                            <td><?php echo htmlspecialchars($employee['job_title']); ?></td>
                            <td>$<?php echo number_format($employee['base_salary'], 2); ?></td>
                            <td><?php echo $employee['payroll_count']; ?></td>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?php echo $employee['id']; ?>">
                                            Delete
                                        </button>
                                    </div>

                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $employee['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete employee 
                                                    <strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong>?
                                                    This will also delete all associated payroll records.
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST">
                                                        <input type="hidden" name="delete_id" value="<?php echo $employee['id']; ?>">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
