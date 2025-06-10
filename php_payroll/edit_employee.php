<?php
require_once 'config.php';
require_once 'db.php';

// Check if user is logged in and is admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: employees.php');
    exit();
}

$error = '';
$success = '';
$employee = null;

// Get employee ID from URL
$id = filter_var($_GET['id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
if (!$id) {
    header('Location: employees.php');
    exit();
}

// Fetch employee data
try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        $_SESSION['error'] = "Employee not found.";
        header('Location: employees.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Fetch Employee Error: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching employee data.";
    header('Location: employees.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $job_title = trim($_POST['job_title'] ?? '');
    $base_salary = filter_var($_POST['base_salary'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($job_title) || empty($base_salary)) {
        $error = 'All fields except phone are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!is_numeric($base_salary) || $base_salary <= 0) {
        $error = 'Please enter a valid base salary.';
    } else {
        try {
            // Check if email exists for other employees
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'An employee with this email already exists.';
            } else {
                // Update employee
                $stmt = $pdo->prepare("
                    UPDATE employees 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, job_title = ?, base_salary = ?
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $job_title, $base_salary, $id]);

                $_SESSION['success'] = "Employee updated successfully.";
                header('Location: employees.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log("Update Employee Error: " . $e->getMessage());
            $error = 'Error updating employee. Please try again.';
        }
    }
}

require_once 'header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Edit Employee</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="employees.php" class="btn btn-secondary">Back to Employees</a>
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
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                    <div class="invalid-feedback">Please enter first name.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                    <div class="invalid-feedback">Please enter last name.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($employee['phone']); ?>">
                    <div class="form-text">Optional</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="job_title" class="form-label">Job Title</label>
                    <input type="text" class="form-control" id="job_title" name="job_title" 
                           value="<?php echo htmlspecialchars($employee['job_title']); ?>" required>
                    <div class="invalid-feedback">Please enter job title.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="base_salary" class="form-label">Base Salary</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="base_salary" name="base_salary" 
                               step="0.01" min="0" 
                               value="<?php echo htmlspecialchars($employee['base_salary']); ?>" required>
                        <div class="invalid-feedback">Please enter a valid base salary.</div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Update Employee</button>
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
</script>

<?php require_once 'footer.php'; ?>
