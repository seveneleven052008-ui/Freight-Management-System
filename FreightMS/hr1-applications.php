<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = 'HR1 - Application Management';
$pdo = getDBConnection();

// Handle "Mark as Qualified" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_qualified') {
    $applicantId = (int)($_POST['applicant_id'] ?? 0);
    if ($applicantId > 0) {
        $stmt = $pdo->prepare("UPDATE applicants SET application_status = 'Qualified' WHERE applicant_id = ?");
        $stmt->execute([$applicantId]);
        header('Location: hr1-applications.php?success=1');
        exit;
    }
}

// Get all applicants
$applicants = $pdo->query("SELECT * FROM applicants ORDER BY created_at DESC")->fetchAll();

ob_start();
?>

<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Applicant Review (HR1)</h1>
        <p class="text-gray-600">Review and screen job applicants for potential qualified candidates.</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span>Applicant successfully marked as qualified and sent to HR2.</span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-900">Applicant Name</th>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-900">Position Applied</th>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-900">Contact Info</th>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-900">Status</th>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">No applicants found in the system.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $applicant): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($applicant['name']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <?php echo htmlspecialchars($applicant['position_applied']); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <?php echo htmlspecialchars($applicant['contact_info']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php 
                                    echo $applicant['application_status'] === 'Hired' ? 'bg-blue-100 text-blue-700' : 
                                        ($applicant['application_status'] === 'Qualified' ? 'bg-green-100 text-green-700' : 
                                        ($applicant['application_status'] === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')); 
                                ?>">
                                    <?php echo htmlspecialchars($applicant['application_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button onclick="viewDetails(<?php echo htmlspecialchars(json_encode($applicant)); ?>)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">View Details</button>
                                <?php if ($applicant['application_status'] === 'Pending'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Mark this applicant as qualified? This will send their data to HR2.');">
                                        <input type="hidden" name="action" value="mark_qualified">
                                        <input type="hidden" name="applicant_id" value="<?php echo $applicant['applicant_id']; ?>">
                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition-colors">Mark as Qualified</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Applicant Information</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Full Name</p>
                <p id="modalName" class="text-gray-900 font-medium"></p>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Position Applied For</p>
                <p id="modalPosition" class="text-gray-900"></p>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Contact Information</p>
                <p id="modalContact" class="text-gray-900"></p>
            </div>
            <div>
                <p class="text-sm text-gray-500 uppercase font-semibold">Current Status</p>
                <p id="modalStatus" class="font-medium"></p>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">Close</button>
        </div>
    </div>
</div>

<script>
function viewDetails(applicant) {
    document.getElementById('modalName').textContent = applicant.name;
    document.getElementById('modalPosition').textContent = applicant.position_applied;
    document.getElementById('modalContact').textContent = applicant.contact_info;
    document.getElementById('modalStatus').textContent = applicant.application_status;
    
    const statusEl = document.getElementById('modalStatus');
    let statusClass = 'text-yellow-600';
    if (applicant.application_status === 'Hired') statusClass = 'text-blue-600';
    else if (applicant.application_status === 'Qualified') statusClass = 'text-green-600';
    else if (applicant.application_status === 'Rejected') statusClass = 'text-red-600';
    
    statusEl.className = 'font-medium ' + statusClass;

    document.getElementById('detailsModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>
