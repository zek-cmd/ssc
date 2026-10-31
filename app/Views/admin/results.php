<?php
$pageTitle = 'Results';
$sidebarNavItems = [];
$mobileNavItems = [];
$extraSidebarNavHtml = '';
$extraMobileNavHtml = '';

$extraSidebarContent = '
    <span id="lastUpdated" class="block text-center text-[10px] text-zinc-500 dark:text-zinc-400 font-medium mb-2 sidebar-label"></span>
    <a href="' . APP_URL . '/admin/dashboard" class="sidebar-link nav-link flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-expanded-item"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg><span class="sidebar-label truncate">Dashboard</span></a>
    <a href="' . APP_URL . '/admin/dashboard" class="sidebar-link nav-link hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="Dashboard"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></a>
    <button id="openVoteLookupBtn" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 text-left sidebar-expanded-item">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <span class="sidebar-label truncate">Vote Lookup</span>
    </button>
    <button id="openVoteLookupBtnCollapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="Vote Lookup">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
    </button>
    <button id="toggleResultsPrivacy" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 text-left sidebar-expanded-item">
        <svg id="toggleResultsPrivacyIcon" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
        <span id="toggleResultsPrivacyLabel" class="sidebar-label truncate">Show Results</span>
    </button>
    <button id="toggleResultsPrivacyCollapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="Show Results">
        <svg id="toggleResultsPrivacyIconCollapsed" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
    </button>
    <button id="toggleRefresh" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-expanded-item">
        <svg id="toggleRefreshIcon" class="w-5 h-5 shrink-0 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span id="toggleRefreshLabel" class="sidebar-label text-zinc-700 dark:text-zinc-300">Auto-refresh: ON</span>
    </button>
    <button id="toggleRefreshCollapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="Auto-refresh">
        <svg id="toggleRefreshIconCollapsed" class="w-5 h-5 shrink-0 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
    </button>
';
if ($canExportCsv) {
    $extraSidebarContent .= '
        <button id="exportCSVBtn" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 text-left sidebar-expanded-item">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="sidebar-label truncate">Export CSV</span>
        </button>
        <button id="exportCSVBtnCollapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="Export CSV">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </button>
    ';
}
if ($canDeleteElection) {
    $extraSidebarContent .= '
        <button id="deleteElectionData" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-red-500 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all duration-300 text-left sidebar-expanded-item">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            <span class="sidebar-label truncate">Delete Data</span>
        </button>
        <button id="deleteElectionDataCollapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-red-500 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all duration-300 sidebar-collapsed-item" title="Delete Data">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    ';
}
$extraMobileContent = '';
?>

<div class="flex h-dvh overflow-hidden bg-zinc-50 dark:bg-zinc-950">
    <?php include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include APP_PATH . '/Views/admin/partials/header.php'; ?>

        <main class="flex-1 overflow-y-auto px-4 py-6 lg:px-8 lg:py-8 pb-20 lg:pb-8">
            <div class="max-w-full mx-auto">
                <div class="mb-6">
                    <div class="card-interactive group"><p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Votes</p><p class="text-4xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white mt-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300" id="totalVotes">-</p></div>
                </div>

                <div id="sectionTotalsCard" class="card mb-4 sm:mb-6 hidden">
                    <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white mb-3">Votes per Section</h3>
                    <div id="sectionTotalsList" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3"></div>
                </div>

                <div id="resultsContainer" class="space-y-4 sm:space-y-6">
                    <div class="card text-center py-12"><p class="text-zinc-500">Loading results...</p></div>
                </div>

                <div id="exportProgressModal" class="fixed inset-0 z-50 hidden">
                    <div id="exportProgressBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl shadow-zinc-500/20 ring-1 ring-zinc-200 dark:ring-zinc-800 p-6 mx-4 sm:mx-0">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Exporting Results</h3>
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

                <div id="voteLookupModal" class="fixed inset-0 z-50 hidden">
                    <div id="voteLookupBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div>
                    <div class="absolute inset-0 sm:inset-auto sm:top-1/2 sm:left-1/2 sm:-translate-x-1/2 sm:-translate-y-1/2 w-full sm:w-[90vw] sm:max-w-2xl bg-white dark:bg-zinc-900 sm:rounded-3xl shadow-2xl shadow-zinc-500/20 ring-1 ring-zinc-200 dark:ring-zinc-800 h-full sm:max-h-[85dvh] flex flex-col">
                        <div class="flex items-center justify-between px-4 py-3 sm:px-5 sm:py-4 border-b border-zinc-200/60 dark:border-zinc-800/60 shrink-0">
                            <div class="flex items-center space-x-2 sm:space-x-3">
                                <button id="voteLookupBackBtn" class="p-1.5 rounded-2xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 hidden">
                                    <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white" id="voteLookupModalTitle">Vote Lookup</h3>
                            </div>
                            <button id="closeVoteLookupBtn" class="p-1.5 rounded-2xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300">
                                <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div id="voteLookupContent" class="flex-1 overflow-y-auto px-4 py-4 sm:px-5 sm:py-5">
                            <div class="relative mb-4">
                                <input 
                                    type="text" 
                                    id="voteLookupSearchInput" 
                                    placeholder="Search by name or email..." 
                                    class="w-full rounded-2xl border-0 py-3 pl-10 pr-4 text-sm sm:text-base text-zinc-900 dark:text-zinc-100 bg-zinc-100 dark:bg-zinc-800 shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-700 placeholder:text-zinc-400 dark:placeholder:text-zinc-500 focus:ring-2 focus:ring-blue-500/50 focus:bg-white dark:focus:bg-zinc-900 transition-all duration-300"
                                >
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 sm:w-5 sm:h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <div id="voteLookupResults" class="space-y-1">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-8">Type a name or email to search</p>
                            </div>
                        </div>
                    </div>
                </div>

             <nav class="lg:hidden fixed bottom-0 left-0 right-0 glass-strong border-t border-zinc-200/60 dark:border-zinc-800/60 z-30">
    <div class="flex justify-around py-1.5">
        <button id="openVoteLookupBtnMobile" class="flex flex-col items-center px-1.5 py-0.5 text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
            <svg class="w-4 h-4 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <span>Lookup</span>
        </button>
        <button id="toggleResultsPrivacyMobile" class="flex flex-col items-center px-1.5 py-0.5 text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
            <svg id="toggleResultsPrivacyMobileIcon" class="w-4 h-4 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
            <span id="toggleResultsPrivacyMobileText">Show</span>
        </button>
        <button id="toggleRefreshMobile" class="flex flex-col items-center px-1.5 py-0.5 text-[10px] text-zinc-500 dark:text-zinc-400 font-medium">
            <svg id="toggleRefreshMobileIcon" class="w-4 h-4 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span id="toggleRefreshMobileText">Auto: ON</span>
        </button>
        <?php if ($canExportCsv): ?><button id="exportCSVBtnMobile" class="flex flex-col items-center px-1.5 py-0.5 text-[10px] text-zinc-500 dark:text-zinc-400 font-medium"><svg class="w-4 h-4 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span>Export CSV</span></button><?php endif; ?>
        <?php if ($canDeleteElection): ?><button id="deleteElectionDataMobile" class="flex flex-col items-center px-1.5 py-0.5 text-[10px] text-red-500 font-medium"><svg class="w-4 h-4 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg><span>Delete</span></button><?php endif; ?>
    </div>
</nav>
<button id="scrollTopFab" type="button" class="fixed right-4 bottom-24 lg:bottom-8 lg:right-8 z-20 w-12 h-12 rounded-full bg-blue-600 text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/40 active:scale-95 transition-all duration-300 opacity-0 pointer-events-none flex items-center justify-center" aria-label="Scroll to top">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
    </svg>
</button>
            </div>
            
        </main>
    </div>
</div>

<script nonce="<?= htmlspecialchars($nonce) ?>">
window.SSC.appUrl = '<?= APP_URL ?>';
window.SSC.csrfToken = '<?= \App\Services\SessionService::getCSRFToken() ?>';
window.SSC.candidateImagesUrl = '<?= APP_URL ?>/candidate_images';
</script>
<script src="<?= \App\Services\AdminSecurityService::asset('local/js/chart.umd.min.js') ?>" nonce="<?= htmlspecialchars(\App\Services\AdminSecurityService::getNonce()) ?>"></script>
<script src="<?= \App\Services\AdminSecurityService::asset('js/admin/results.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>
<?php
$pdo = \App\Core\Model::db();
$plRows = $pdo->query("SELECT name, badge_color, badge_text_color FROM partylists")->fetchAll();
if ($plRows):
?>
<style nonce="<?= htmlspecialchars($nonce) ?>">
.pl-badge { background-color: var(--pl-color, #1e40af); color: var(--pl-text, #ffffff); }
<?php foreach ($plRows as $pl): ?>
.pl-badge[data-partylist-badge="<?= htmlspecialchars($pl['name'], ENT_QUOTES) ?>"] { --pl-color: <?= htmlspecialchars($pl['badge_color'] ?: '#1e40af') ?>; --pl-text: <?= htmlspecialchars($pl['badge_text_color'] ?: '#ffffff') ?>; }
<?php endforeach; ?>
</style>
<?php endif; ?>