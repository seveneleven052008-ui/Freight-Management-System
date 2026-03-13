<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';

// Handle Validation Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $leave_id = $_POST['leave_id'];
    $employee_id = $_POST['employee_id'];
    $leave_date = $_POST['leave_date'];
    $shift = $_POST['shift'] ?? 'Day Shift (8:00 AM - 5:00 PM)';

    // Get employee name
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee_name = $stmt->fetchColumn();

    if ($employee_name) {
        try {
            $pdo->beginTransaction();

            if ($action === 'validate_leave') {
                // Insert record as Validated
                $stmt = $pdo->prepare("INSERT INTO leave_validation (employee_id, employee_name, leave_date, shift, validation_status) VALUES (?, ?, ?, ?, 'Validated')");
                $stmt->execute([$employee_id, $employee_name, $leave_date, $shift]);
                $message = "Leave for " . htmlspecialchars($employee_name) . " has been Validated and scheduled.";
            } elseif ($action === 'reject_leave') {
                // Insert record as Rejected
                $stmt = $pdo->prepare("INSERT INTO leave_validation (employee_id, employee_name, leave_date, shift, validation_status) VALUES (?, ?, ?, ?, 'Rejected')");
                $stmt->execute([$employee_id, $employee_name, $leave_date, $shift]);
                $message = "Leave validation for " . htmlspecialchars($employee_name) . " has been Rejected.";
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Get Approved or Rejected Leave Requests that haven't been validated/rejected yet by HR3
$query = "
    SELECT lr.*, u.full_name, u.department, u.position as shift_info
    FROM leave_requests lr
    JOIN users u ON lr.employee_id = u.id
    WHERE lr.status IN ('Approved', 'Rejected')
    AND NOT EXISTS (
        SELECT 1 FROM leave_validation lv 
        WHERE lv.employee_id = lr.employee_id 
        AND lv.leave_date = lr.start_date
    )
    ORDER BY lr.applied_date DESC
";
$processedRequests = $pdo->query($query)->fetchAll();

ob_start();
?>

<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">HR3 - Shift & Scheduling</h1>
        <p class="text-gray-600">Review and validate employee leave requests (Approved/Rejected) against work schedules.</p>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
            <div class="flex items-center">
                <i class="fas <?php echo strpos($message, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> mr-2"></i>
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-800">Leaves Awaiting Final Validation</h2>
            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-semibold">
                <?php echo count($processedRequests); ?> Total
            </span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Leave Type</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assigned Shift</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Final Validation</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($processedRequests)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-clipboard-check text-4xl mb-4 opacity-20"></i>
                                    <p>No processed leave requests waiting for HR3 validation.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($processedRequests as $req): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-indigo-700 font-bold"><?php echo strtoupper(substr($req['full_name'], 0, 1)); ?></span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($req['full_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($req['department']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                        <?php echo htmlspecialchars($req['leave_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $req['status'] === 'Approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?php echo htmlspecialchars($req['status']); ?>
                                    </span>
                                    <div class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($req['start_date']); ?> to <?php echo htmlspecialchars($req['end_date']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium">
                                        <i class="fas fa-clock text-gray-400 mr-2"></i>
                                        <?php echo htmlspecialchars($req['shift_info'] ?: 'Day Shift (8:00 AM - 5:00 PM)'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="" class="inline-block">
                                            <input type="hidden" name="action" value="validate_leave">
                                            <input type="hidden" name="leave_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="employee_id" value="<?php echo $req['employee_id']; ?>">
                                            <input type="hidden" name="leave_date" value="<?php echo $req['start_date']; ?>">
                                            <input type="hidden" name="shift" value="<?php echo htmlspecialchars($req['shift_info'] ?: 'Day Shift (8:00 AM - 5:00 PM)'); ?>">
                                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-semibold flex items-center gap-1.5">
                                                <i class="fas fa-check-circle"></i> Validate
                                            </button>
                                        </form>
                                        <form method="POST" action="" class="inline-block">
                                            <input type="hidden" name="action" value="reject_leave">
                                            <input type="hidden" name="leave_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="employee_id" value="<?php echo $req['employee_id']; ?>">
                                            <input type="hidden" name="leave_date" value="<?php echo $req['start_date']; ?>">
                                            <input type="hidden" name="shift" value="<?php echo htmlspecialchars($req['shift_info'] ?: 'Day Shift (8:00 AM - 5:00 PM)'); ?>">
                                            <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold flex items-center gap-1.5">
                                                <i class="fas fa-times-circle"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div></div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
