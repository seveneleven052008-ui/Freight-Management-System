<?php
require_once 'config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $request_id = $_POST['request_id'];

    if ($action === 'update_status') {
        $new_status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE skill_development SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $request_id])) {
            $message = "Request status updated to " . htmlspecialchars($new_status) . "!";
        } else {
            $message = "Error updating request.";
        }
    } elseif ($action === 'schedule_training') {
        $program_id = $_POST['program_id'];
        $date = $_POST['session_date'];
        $time = $_POST['session_time'];
        $location = $_POST['location'];
        $instructor = $_POST['instructor'];

        $pdo->beginTransaction();
        try {
            // 1. Insert into training_schedule
            $ins = $pdo->prepare("INSERT INTO training_schedule (training_program_id, session_date, session_time, session_type, location, instructor) VALUES (?, ?, ?, 'Seminar', ?, ?)");
            $ins->execute([$program_id, $date, $time, $location, $instructor]);

            // 2. Update skill_development
            $upd = $pdo->prepare("UPDATE skill_development SET status = 'Scheduled', training_program_id = ? WHERE id = ?");
            $upd->execute([$program_id, $request_id]);

            $pdo->commit();
            $message = "Training scheduled successfully and added to the calendar!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error scheduling training: " . $e->getMessage();
        }
    }
}

// Get requests sent by HR1
$requests = $pdo->query("SELECT * FROM skill_development WHERE status = 'Sent' ORDER BY created_at DESC")->fetchAll();

// Get available training programs for the modal
$programs = $pdo->query("SELECT id, title FROM training_programs ORDER BY title")->fetchAll();

ob_start();
?>

<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">HR2 - Training Requests</h1>
        <p class="text-gray-600">Review and manage skill development requests from HR1.</p>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Pending Requests (Sent by HR1)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required Skill</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposed Program</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">No new training requests from HR1 at the moment.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($req['employee_name']); ?></div>
                                    <div class="text-xs text-gray-500">ID: <?php echo $req['employee_id']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo htmlspecialchars($req['required_skill']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo htmlspecialchars($req['training_program']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex gap-2">
                                        <button onclick="openScheduleModal(<?php echo $req['id']; ?>, '<?php echo addslashes($req['employee_name']); ?>', '<?php echo addslashes($req['required_skill']); ?>')" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                            Schedule
                                        </button>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                            <button name="status" value="Completed" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                                                Complete
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
    </div>
</div>

<!-- Scheduling Modal -->
<div id="scheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Schedule Training</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="action" value="schedule_training">
            <input type="hidden" name="request_id" id="modalRequestId">
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Employee: <span id="displayEmployee" class="font-semibold text-gray-900"></span></p>
                    <p class="text-sm text-gray-600">Skill: <span id="displaySkill" class="font-semibold text-gray-900"></span></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Training Program</label>
                    <select name="program_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">Choose Program</option>
                        <?php foreach ($programs as $prog): ?>
                            <option value="<?php echo $prog['id']; ?>"><?php echo htmlspecialchars($prog['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="session_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" name="session_time" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" name="location" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Conference Room A">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instructor</label>
                    <input type="text" name="instructor" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="e.g. John Doe">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold">Save & Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
function openScheduleModal(id, name, skill) {
    document.getElementById('modalRequestId').value = id;
    document.getElementById('displayEmployee').textContent = name;
    document.getElementById('displaySkill').textContent = skill;
    document.getElementById('scheduleModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('scheduleModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
