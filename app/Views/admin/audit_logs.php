<?php
$pageTitle = 'Audit Logs';
$sidebarNavItems = [
    ['url' => APP_URL . '/admin/dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
];
$mobileNavItems = [];
$extraSidebarContent = '
    <button id="exportCSVBtn" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-expanded-item"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="sidebar-label truncate">Export CSV</span></button>
    <button id="exportCSVBtnCollapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="Export CSV"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></button>
    <button id="clearLogsBtn" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-red-500 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all duration-300 sidebar-expanded-item"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg><span class="sidebar-label truncate">Clear All Logs</span></button>
    <button id="clearLogsBtnCollapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-red-500 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all duration-300 sidebar-collapsed-item" title="Clear All Logs"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
';
$extraMobileContent = '
    <button id="exportCSVBtnMobile" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span>Export CSV</span></button>
    <button id="clearLogsBtnMobile" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-red-500 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg><span>Clear All Logs</span></button>
';
?>

<div class="flex h-dvh overflow-hidden bg-zinc-50 dark:bg-zinc-950">
    <?php include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include APP_PATH . '/Views/admin/partials/header.php'; ?>

        <main class="flex-1 overflow-y-auto px-4 py-6 lg:px-8 lg:py-8 pb-20 lg:pb-8">
            <div class="max-w-full mx-auto">
                <div class="card mb-6">
                    <div class="mb-4"><input type="search" id="logSearch" placeholder="Search logs..." autocomplete="off" class="input-field w-full"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="thead-desktop">
                                <tr class="border-b border-zinc-200/60 dark:border-zinc-800/60">
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Date/Time</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Admin</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Action</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Entity</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Description</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">IP Address</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col items-start mt-4 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 gap-2">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400" id="logsInfo"></p>
                        <div class="flex gap-2 flex-wrap" id="logsPages"></div>
                    </div>
                </div>

                <div id="exportProgressModal" class="fixed inset-0 z-50 hidden">
                    <div class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl shadow-zinc-500/20 ring-1 ring-zinc-200 dark:ring-zinc-800 p-6 sm:mx-0">
                        <div class="flex items-center justify-between mb-4">
                            <button id="closeExportProgressBtn" class="p-1.5 rounded-2xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 hidden">
                                <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="mb-3">
                            <div class="w-full h-3 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div id="exportProgressBar" class="h-full bg-blue-600 rounded-full transition-all duration-300 w-0"></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <p id="exportProgressText" class="text-sm text-zinc-500 dark:text-zinc-400">Preparing export...</p>
                            <p id="exportProgressPercent" class="text-sm font-semibold text-zinc-900 dark:text-white">0%</p>
                        </div>
                    </div>
                </div>

              <div id="auditDetailModal" class="fixed inset-0 z-50 hidden">
    <div id="auditDetailBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div>
    <div class="absolute inset-0 w-full h-full bg-white dark:bg-zinc-900 flex flex-col sm:inset-auto sm:top-1/2 sm:left-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 sm:w-full sm:max-w-lg sm:h-auto sm:max-h-[80dvh] sm:rounded-3xl sm:shadow-2xl sm:shadow-zinc-500/20 sm:ring-1 sm:ring-zinc-200 dark:sm:ring-zinc-800">
        <div class="flex items-center justify-between px-4 py-3 sm:px-6 sm:py-4 border-b border-zinc-200/60 dark:border-zinc-800/60 shrink-0">
            <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">Audit Log Details</h3>
            <button id="closeAuditDetailBtn" class="p-1.5 rounded-2xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300">
                <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-6">
            <div id="auditDetailContent">
                <div class="text-center py-8 text-zinc-500">Loading...</div>
            </div>
        </div>
        <div class="px-4 py-3 sm:px-6 sm:py-4 border-t border-zinc-200/60 dark:border-zinc-800/60 shrink-0">
            <button type="button" id="closeAuditDetailFooterBtn" class="btn-secondary w-full">Close</button>
        </div>
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
<script src="<?= \App\Services\AdminSecurityService::asset('js/admin/audit_logs.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>