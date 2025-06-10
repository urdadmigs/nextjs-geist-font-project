<?php
require_once 'config.php';
require_once 'db.php';

// Check if user is logged in and is admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: payroll.php');
    exit();
}

$error = '';
$success = '';

// Fetch all employees for dropdown
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, base_salary FROM employees ORDER BY last_name, first_name");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching employees.";
    header('Location: payroll.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $employee_id = filter_var($_POST['employee_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
    $pay_date = trim($_POST['pay_date'] ?? '');
    $gross_salary = filter_var($_POST['gross_salary'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $deductions = filter_var($_POST['deductions'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $bonuses = filter_var($_POST['bonuses'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Calculate net salary
    $net_salary = $gross_salary - $deductions + $bonuses;

    // Validation
    if (!$employee_id || empty($pay_date)) {
        $error = 'Please select an employee and pay date.';
    } elseif (!is_numeric($gross_salary) || $gross_salary <= 0) {
        $error = 'Please enter a valid gross salary.';
    } elseif (!is_numeric($deductions) || $deductions < 0) {
        $error = 'Deductions cannot be negative.';
    } elseif (!is_numeric($bonuses) || $bonuses < 0) {
        $error = 'Bonuses cannot be negative.';
    } elseif ($net_salary <= 0) {
        $error = 'Net salary cannot be negative or zero.';
    } else {
        try {
            // Check if a payroll record already exists for this employee and date
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM payroll WHERE employee_id = ? AND pay_date = ?");
            $stmt->execute([$employee_id, $pay_date]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'A payroll record already exists for this employee on the selected date.';
            } else {
                // Insert new payroll record
                $stmt = $pdo->prepare("
                    INSERT INTO payroll (employee_id, pay_date, gross_salary, deductions, bonuses, net_salary)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$employee_id, $pay_date, $gross_salary, $deductions, $bonuses, $net_salary]);

                $_SESSION['success'] = "Payroll record added successfully.";
                header('Location: payroll.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log("Add Payroll Error: " . $e->getMessage());
            $error = 'Error adding payroll record. Please try again.';
        }
    }
}

require_once 'header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add New Payroll Record</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="payroll.php" class="btn btn-secondary">Back to Payroll</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select class="form-select" id="employee_id" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>" 
                                    data-base-salary="<?php echo $employee['base_salary']; ?>"
                                    <?php echo isset($_POST['employee_id']) && $_POST['employee_id'] == $employee['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select an employee.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="pay_date" class="form-label">Pay Date</label>
                    <input type="date" class="form-control" id="pay_date" name="pay_date" 
                           value="<?php echo htmlspecialchars($_POST['pay_date'] ?? ''); ?>" required>
                    <div class="invalid-feedback">Please select a pay date.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="gross_salary" class="form-label">Gross Salary</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="gross_salary" name="gross_salary" 
                               step="0.01" min="0" 
                               value="<?php echo htmlspecialchars($_POST['gross_salary'] ?? ''); ?>" required>
                        <div class="invalid-feedback">Please enter a valid gross salary.</div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="deductions" class="form-label">Deductions</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="deductions" name="deductions" 
                               step="0.01" min="0" 
                               value="<?php echo htmlspecialchars($_POST['deductions'] ?? '0.00'); ?>" required>
                        <div class="invalid-feedback">Please enter valid deductions.</div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="bonuses" class="form-label">Bonuses</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="bonuses" name="bonuses" 
                               step="0.01" min="0" 
                               value="<?php echo htmlspecialchars($_POST['bonuses'] ?? '0.00'); ?>" required>
                        <div class="invalid-feedback">Please enter valid bonuses.</div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Net Salary</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" id="net_salary" readonly>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Add Payroll Record</button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Auto-fill gross salary based on employee selection
document.getElementById('employee_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const baseSalary = selectedOption.getAttribute('data-base-salary');
    document.getElementById('gross_salary').value = baseSalary || '';
    calculateNetSalary();
});

// Calculate net salary
function calculateNetSalary() {
    const gross = parseFloat(document.getElementById('gross_salary').value) || 0;
    const deductions = parseFloat(document.getElementById('deductions').value) || 0;
    const bonuses = parseFloat(document.getElementById('bonuses').value) || 0;
    const net = gross - deductions + bonuses;
    document.getElementById('net_salary').value = net.toFixed(2);
}

// Add event listeners for calculation
['gross_salary', 'deductions', 'bonuses'].forEach(function(id) {
    document.getElementById(id).addEventListener('input', calculateNetSalary);
});

// Initial calculation
calculateNetSalary();
</script>

<?php require_once 'footer.php'; ?>
