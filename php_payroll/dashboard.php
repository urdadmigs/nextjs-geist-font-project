<?php
require_once 'config.php';
require_once 'db.php';

// Check if user is logged in
requireLogin();

// Fetch summary data
try {
    // Total number of employees
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $employeeCount = $stmt->fetch()['total'];

    // Total monthly payroll
    $stmt = $pdo->query("SELECT SUM(net_salary) as total FROM payroll WHERE MONTH(pay_date) = MONTH(CURRENT_DATE())");
    $monthlyPayroll = $stmt->fetch()['total'] ?? 0;

    // Recent payroll entries
    $stmt = $pdo->query("
        SELECT p.*, e.first_name, e.last_name 
        FROM payroll p 
        JOIN employees e ON p.employee_id = e.id 
        ORDER BY p.pay_date DESC 
        LIMIT 5
    ");
    $recentPayroll = $stmt->fetchAll();

    // Department salary statistics (using job titles as departments)
    $stmt = $pdo->query("
        SELECT job_title, 
               COUNT(*) as employee_count,
               AVG(base_salary) as avg_salary,
               SUM(base_salary) as total_salary
        FROM employees
        GROUP BY job_title
    ");
    $departmentStats = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error = "Error loading dashboard data.";
}

require_once 'header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Dashboard</h2>
    </div>
    <div class="col-md-6 text-end">
        <span class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Employees</h5>
                <h2 class="card-text"><?php echo number_format($employeeCount); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Monthly Payroll</h5>
                <h2 class="card-text">$<?php echo number_format($monthlyPayroll, 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Department Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Department Statistics</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Employees</th>
                                <th>Average Salary</th>
                                <th>Total Salary</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departmentStats as $dept): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dept['job_title']); ?></td>
                                <td><?php echo $dept['employee_count']; ?></td>
                                <td>$<?php echo number_format($dept['avg_salary'], 2); ?></td>
                                <td>$<?php echo number_format($dept['total_salary'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payroll -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Recent Payroll Entries</h5>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayroll as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($entry['pay_date'])); ?></td>
                                <td>$<?php echo number_format($entry['gross_salary'], 2); ?></td>
                                <td>$<?php echo number_format($entry['deductions'], 2); ?></td>
                                <td>$<?php echo number_format($entry['bonuses'], 2); ?></td>
                                <td>$<?php echo number_format($entry['net_salary'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Salary Distribution Chart -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Salary Distribution by Department</h5>
                <canvas id="salaryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize the chart after the page loads
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salaryChart').getContext('2d');
    
    // Prepare data from PHP
    const departments = <?php echo json_encode(array_column($departmentStats, 'job_title')); ?>;
    const averageSalaries = <?php echo json_encode(array_column($departmentStats, 'avg_salary')); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: departments,
            datasets: [{
                label: 'Average Salary',
                data: averageSalaries,
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                borderColor: 'rgba(0, 0, 0, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>
