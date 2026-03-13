<?php
requireLogin();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-64 text-white flex flex-col min-h-screen" style="background-color: #05386D;">
    <div class="p-6 border-b border-indigo-800" style="margin-left: -10px;">
        <div class="flex items-center gap-3">
            <img src="image/costa.png" alt="Costa Cargo Logo" class="w-12 h-12">
            <div>
                <h2 class="text-white font-semibold">COSTA CARGO</h2>
                <p class="text-indigo-300 text-sm">Freight System</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 p-4 space-y-1">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'dashboard.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
            <i class="fas fa-chart-line w-5"></i>
            <span>Dashboard</span>
        </a>
        <a href="succession-planning.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'succession-planning.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
            <i class="fas fa-users w-5"></i>
            <span>Succession Planning</span>
        </a>
        <a href="training-management.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'training-management.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
            <i class="fas fa-graduation-cap w-5"></i>
            <span>Training</span>
        </a>
        <a href="competency-management.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'competency-management.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
            <i class="fas fa-award w-5"></i>
            <span>Competency</span>
        </a>
        <a href="ess.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'ess.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
            <i class="fas fa-user w-5"></i>
            <span>ESS</span>
        </a>
        <a href="learning.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'learning.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
            <i class="fas fa-book-open w-5"></i>
            <span>Learning</span>
        </a>

        
        <?php 
        $user_id = $_SESSION['user_id'] ?? 0;
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $currUser = $stmt->fetch();
        $roleVal = strtolower($currUser['role'] ?? '');
        $is_admin = ($roleVal === 'admin') || (($currUser['username'] ?? '') === 'admin');

        if ($is_admin): ?>
            <div class="pt-4 mt-4 border-t border-indigo-800">
                <p class="px-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-2">Admin Tools</p>
                <a href="payroll-management.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'payroll-management.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
                    <i class="fas fa-file-invoice-dollar w-5"></i>
                    <span>Payroll</span>
                </a>
                <a href="hr1-applications.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'hr1-applications.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
                    <i class="fas fa-users-cog w-5"></i>
                    <span>HR1 - Applications</span>
                </a>
                <a href="hr2-qualified-applicants.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'hr2-qualified-applicants.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
                    <i class="fas fa-user-check w-5"></i>
                    <span>HR2 - Qualified</span>
                </a>
                <a href="hr1-skill-development.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'hr1-skill-development.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
                    <i class="fas fa-brain w-5"></i>
                    <span>HR1 - Skill Identification</span>
                </a>
                <a href="hr2-training-requests.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'hr2-training-requests.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
                    <i class="fas fa-tasks w-5"></i>
                    <span>HR2 - Training Requests</span>
                </a>
                <a href="hr4-talent-identification.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'hr4-talent-identification.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
                    <i class="fas fa-user-plus w-5"></i>
                    <span>HR4 - Talent Identification</span>
                </a>
                <a href="hr3-shift-scheduling.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage == 'hr3-shift-scheduling.php' ? 'bg-indigo-800 text-white' : 'text-indigo-200 hover:bg-indigo-800/50'; ?>">
                    <i class="fas fa-clock w-5"></i>
                    <span>HR3 - Shift & Scheduling</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-indigo-800">
        <div class="flex items-center gap-3 mb-4 px-4">
            <div class="w-10 h-10 bg-indigo-700 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-sm"></i>
            </div>
            <div>
                <p class="text-white"><?php echo htmlspecialchars(getUserName()); ?></p>
                <p class="text-indigo-300 text-sm"><?php echo htmlspecialchars(getUserPosition()); ?></p>
            </div>
        </div>
        <a href="logout.php" class="w-full flex items-center gap-3 px-4 py-3 text-indigo-200 hover:bg-indigo-800/50 rounded-lg transition-colors">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
