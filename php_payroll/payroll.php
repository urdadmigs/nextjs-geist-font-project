<?php
require_once 'config.php';
require_once 'db.php';

// Check if user is logged in
requireLogin();

// Handle delete request
if (isset($_POST['delete_id']) && $_SESSION['role'] === 'admin') {
    try {
        $stmt = $pdo->prepare("DELETE FROM payroll WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $_SESSION['success'] = "Payroll record deleted successfully.";
        header("Location: payroll.php");
        exit();
    } catch (PDOException $e) {
        error_log("Delete Error: " . $e->getMessage());
        $_SESSION['error'] = "Error deleting payroll record.";
    }
}

// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

// Fetch all employees for filter dropdown
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM employees ORDER BY last_name, first_name");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
    $employees = [];
}

// Build the payroll query
$query = "
    SELECT p.*, e.first_name, e.last_name 
    FROM payroll p 
    JOIN employees e ON p.employee_id = e.id 
    WHERE 1=1
";
$params = [];

if ($month) {
    $query .= " AND DATE_FORMAT(p.pay_date, '%Y-%m') = ?";
    $params[] = $month;
}

if ($employee_id) {
    $query .= " AND p.employee_id = ?";
    $params[] = $employee_id;
}

$query .= " ORDER BY p.pay_date DESC, e.last_name, e.first_name";

// Fetch payroll records
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payroll_records = $stmt->fetchAll();

    // Calculate totals
    $total_gross = 0;
    $total_deductions = 0;
    $total_bonuses = 0;
    $total_net = 0;
    foreach ($payroll_records as $record) {
        $total_gross += $record['gross_salary'];
        $total_deductions += $record['deductions'];
        $total_bonuses += $record['bonuses'];
        $total_net += $record['net_salary'];
    }
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching payroll records.";
    $payroll_records = [];
}

require_once 'header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payroll Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="add_payroll.php" class="btn btn-primary">
                Add New Payroll Record
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="month" class="form-label">Month</label>
                <input type="month" class="form-control" id="month" name="month" 
                       value="<?php echo htmlspecialchars($month); ?>">
            </div>
            <div class="col-md-4">
                <label for="employee_id" class="form-label">Employee</label>
                <select class="form-select" id="employee_id" name="employee_id">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" 
                            <?php echo $employee_id == $emp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="payroll.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Payroll Records Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Pay Date</th>
                        <th>Gross Salary</th>
                        <th>Deductions</th>
                        <th>Bonuses</th>
                        <th>Net Salary</th>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payroll_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['pay_date'])); ?></td>
                            <td>$<?php echo number_format($record['gross_salary'], 2); ?></td>
                            <td>$<?php echo number_format($record['deductions'], 2); ?></td>
                            <td>$<?php echo number_format($record['bonuses'], 2); ?></td>
                            <td>$<?php echo number_format($record['net_salary'], 2); ?></td>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit_payroll.php?id=<?php echo $record['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?php echo $record['id']; ?>">
                                            Delete
                                        </button>
                                    </div>

                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $record['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete this payroll record?
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST">
                                                        <input type="hidden" name="delete_id" value="<?php echo $record['id']; ?>">
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
                <tfoot>
                    <tr class="table-dark">
                        <td><strong>Totals</strong></td>
                        <td></td>
                        <td><strong>$<?php echo number_format($total_gross, 2); ?></strong></td>
                        <td><strong>$<?php echo number_format($total_deductions, 2); ?></strong></td>
                        <td><strong>$<?php echo number_format($total_bonuses, 2); ?></strong></td>
                        <td><strong>$<?php echo number_format($total_net, 2); ?></strong></td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td></td>
                        <?php endif; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
