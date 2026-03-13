<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_to_hr2') {
    $employee_id = $_POST['employee_id'];
    $talent_type = $_POST['talent_type'];

    // Get employee name
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee_name = $stmt->fetchColumn();

    if ($employee_name) {
        $stmt = $pdo->prepare("INSERT INTO talent_identification (employee_id, employee_name, talent_type, status) VALUES (?, ?, ?, 'Sent')");
        if ($stmt->execute([$employee_id, $employee_name, $talent_type])) {
            $message = "Talent data for " . htmlspecialchars($employee_name) . " sent to HR2 successfully!";
        } else {
            $message = "Error sending talent data.";
        }
    }
}

// Get all employees for selection
$employees = $pdo->query("SELECT id, full_name, department FROM users WHERE role != 'admin' ORDER BY full_name")->fetchAll();

// Get recent identifications
$identifications = $pdo->query("SELECT * FROM talent_identification ORDER BY created_at DESC LIMIT 10")->fetchAll();

ob_start();
?>

<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">HR4 - Talent Identification</h1>
        <p class="text-gray-600">Identify Key Role Talent and High Potential Employees for HR2 modules.</p>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Identification Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Identify Talent</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="send_to_hr2">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Employee</label>
                            <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">Choose Employee</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name'] . ' (' . $emp['department'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Talent Category</label>
                            <select name="talent_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="Key Role Talent">Key Role Talent (→ Competency Module)</option>
                                <option value="High Potential">High Potential (→ Succession Planning)</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-bold">
                            <i class="fas fa-paper-plane mr-2"></i> Send to HR2
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Identifications -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Identifications</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($identifications)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No talent identified yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($identifications as $idnt): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($idnt['employee_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $idnt['talent_type'] === 'High Potential' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                                <?php echo htmlspecialchars($idnt['talent_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $idnt['talent_type'] === 'High Potential' ? 'Succession Module' : 'Competency Module'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                <?php echo htmlspecialchars($idnt['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
