<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = 'Payroll Management';
$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get user info to check role
$userInfo = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userInfo->execute([$userId]);
$personalInfo = $userInfo->fetch();

// Determine if current user should be treated as an admin
$roleValue = strtolower($personalInfo['role'] ?? '');
$isAdmin = ($roleValue === 'admin') || (($personalInfo['username'] ?? '') === 'admin');

if (!$isAdmin) {
    // Redirect non-admins to ESS
    header('Location: ess.php');
    exit;
}

// Handle Filters
$statusFilter = $_GET['status'] ?? '';
$monthFilter = $_GET['month'] ?? '';

// Build Query for all Payslips
$query = "
    SELECT 
        p.*, 
        u.full_name,
        u.department,
        u.position
    FROM payslips p
    JOIN users u ON p.employee_id = u.id
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $query .= " AND p.status = ?";
    $params[] = $statusFilter;
}

if ($monthFilter) {
    $query .= " AND p.month LIKE ?";
    $params[] = "%$monthFilter%";
}

$query .= " ORDER BY p.period_start DESC, u.full_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payslips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct months for filter dropdown
$monthsStmt = $pdo->query("SELECT DISTINCT month FROM payslips ORDER BY period_start DESC");
$availableMonths = $monthsStmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate Summary Stats
$stats = [
    'total_payslips' => count($payslips),
    'total_gross' => 0,
    'total_net' => 0,
    'total_deductions' => 0
];

foreach ($payslips as $slip) {
    $stats['total_gross'] += $slip['gross_pay'];
    $stats['total_net'] += $slip['net_pay'];
    $stats['total_deductions'] += $slip['deductions'];
}

ob_start();
?>

<div class="p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Payroll Administration</h1>
            <p class="text-gray-600">
                View and manage all employee payslip records synchronized from the HR4 system.
            </p>
        </div>
        <div class="flex gap-3">
             <button onclick="window.location.href='api/generate_key.php?service=HR4_System'" class="flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors border border-indigo-200" title="Generate API token for HR4 integration">
                <i class="fas fa-key"></i>
                <span>API Keys</span>
            </button>
            <a href="api/export_payroll.php?month=<?php echo urlencode($monthFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-file-export"></i>
                <span>Export Report</span>
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
            <div class="flex items-center gap-4">
                <div class="bg-indigo-100 p-3 rounded-lg text-indigo-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Payslips</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_payslips']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-center gap-4">
                <div class="bg-blue-100 p-3 rounded-lg text-blue-600">
                    <i class="fas fa-coins text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Gross Pay</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['total_gross']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
            <div class="flex items-center gap-4">
                <div class="bg-red-100 p-3 rounded-lg text-red-600">
                    <i class="fas fa-hand-holding-usd text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Deductions</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['total_deductions']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex items-center gap-4">
                <div class="bg-green-100 p-3 rounded-lg text-green-600">
                    <i class="fas fa-money-check-alt text-xl"></i>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Net Pay</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo formatCurrency($stats['total_net']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-4 border-b border-gray-200">
            <form id="filterForm" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Month</label>
                    <select name="month" class="w-full sm:w-64 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 border" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Months</option>
                        <?php foreach($availableMonths as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $monthFilter === $m ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                    <select name="status" class="w-full sm:w-64 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-2 border" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Statuses</option>
                        <option value="Paid" <?php echo $statusFilter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <?php if ($statusFilter || $monthFilter): ?>
                    <div>
                        <a href="payroll-management.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Payslips Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($payslips)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                No payslip records found matching the criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payslips as $slip): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                            <span class="text-indigo-700 font-bold"><?php echo substr($slip['full_name'], 0, 1); ?></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($slip['full_name']); ?></div>
                                            <div class="text-sm text-gray-500">ID: <?php echo htmlspecialchars($slip['employee_id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($slip['department']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($slip['position']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($slip['month']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo formatDate($slip['period_start']); ?> - <?php echo formatDate($slip['period_end']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                    <?php echo formatCurrency($slip['gross_pay']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-right">
                                    <?php echo formatCurrency($slip['deductions']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right font-bold">
                                    <?php echo formatCurrency($slip['net_pay']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadge($slip['status']); ?>">
                                        <?php echo htmlspecialchars($slip['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg" title="View Payslip PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'includes/layout.php';
?>
