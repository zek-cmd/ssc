<?php
$pageTitle = 'Security Events';
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
                    <div class="mb-4">
                        <input type="search" id="eventSearch" placeholder="Search security events..." autocomplete="off" class="input-field w-full">
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="thead-desktop">
                                <tr class="border-b border-zinc-200/60 dark:border-zinc-800/60">
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Date/Time</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Admin</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Event Type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Description</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">IP Address</th>
                                </tr>
                            </thead>
                            <tbody id="eventsTableBody">
                                <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col items-start mt-4 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 gap-2">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400" id="eventsInfo"></p>
                        <div class="flex gap-2 flex-wrap" id="eventsPages"></div>
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
<script src="<?= \App\Services\AdminSecurityService::asset('js/admin/security_events.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>