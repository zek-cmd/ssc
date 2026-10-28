<?php
$pageTitle = 'Sessions';
$sidebarNavItems = [
    ['url' => APP_URL . '/admin/dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
];
$mobileNavItems = [];
$extraSidebarContent = '';
$extraMobileContent = '';
?>

<div class="flex h-dvh overflow-hidden bg-zinc-50 dark:bg-zinc-950">
    <?php include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include APP_PATH . '/Views/admin/partials/header.php'; ?>

        <main class="flex-1 overflow-y-auto px-4 py-6 lg:px-8 lg:py-8 pb-20 lg:pb-8">
            <div class="max-w-full mx-auto">
                <div class="card mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Active Sessions</h2>
                        </div>
                        <button type="button" id="refreshSessionsBtn" class="px-4 py-2 rounded-2xl text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all duration-300">Refresh</button>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 mb-4">
                        <input type="search" id="sessionsSearch" placeholder="Search voter, IP, or browser..." class="flex-1 px-4 py-2 rounded-2xl text-sm bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 border border-transparent focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-600">
                    </div>
                    <div id="sessionsCards" class="lg:hidden space-y-3"></div>
                    <div id="sessionsTableWrap" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="thead-desktop">
                                <tr class="border-b border-zinc-200/60 dark:border-zinc-800/60">
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Voter</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Host name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">IP address</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Device</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Browser</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Last activity</th>
                                    <th class="px-4 py-3 text-right font-semibold text-zinc-500 dark:text-zinc-400">Action</th>
                                </tr>
                            </thead>
                            <tbody id="sessionsTableBody">
                                <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col items-start mt-4 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 gap-2">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400" id="sessionsInfo"></p>
                        <div class="flex gap-2 flex-wrap" id="sessionsPages"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script nonce="<?= htmlspecialchars($nonce) ?>">
window.SSC.appUrl = '<?= APP_URL ?>';
window.SSC.csrfToken = '<?= \App\Services\SessionService::getCSRFToken() ?>';
</script>
<script src="<?= \App\Services\AdminSecurityService::asset('js/admin/sessions.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>
