<?php
$pageTitle = 'Dashboard';
$sidebarNavItems = [
    ['url' => APP_URL . '/admin/results', 'label' => 'Results', 'icon' => 'results'],
];
$extraSidebarContent = '';
$extraMobileContent = '';
$extraSidebarNavHtml = '';
$extraMobileNavHtml = '';

if (isset($canManageAccounts) && $canManageAccounts) {
    $sidebarNavItems[] = ['url' => APP_URL . '/admin/account_management', 'label' => 'Accounts', 'icon' => 'accounts'];
}

if (isset($canManageAuditLogs) && $canManageAuditLogs) {
    $sidebarNavItems[] = ['url' => APP_URL . '/admin/audit-logs', 'label' => 'Audit Logs', 'icon' => 'audit'];
    $sidebarNavItems[] = ['url' => APP_URL . '/admin/security-events', 'label' => 'Security Events', 'icon' => 'security'];
}

if (isset($canManageVote) && $canManageVote) {
    $extraSidebarNavHtml = '
    <button type="button" id="votingControlsBtn" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-expanded-item">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span class="sidebar-label">Voting Controls</span>
    </button>
    <button type="button" id="votingControlsBtnCollapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="Voting Controls">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </button>
    ';
    $extraMobileNavHtml = '
    <button type="button" id="votingControlsBtnMobile" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>Voting Controls</span></button>
    ';
}

$mobileNavItems = $sidebarNavItems;
?>

<div class="flex h-dvh overflow-hidden bg-zinc-50 dark:bg-zinc-950">
    <?php include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include APP_PATH . '/Views/admin/partials/header.php'; ?>

        <main class="flex-1 overflow-y-auto px-4 py-6 lg:px-8 lg:py-8 pb-20 lg:pb-8">
            <div class="max-w-full mx-auto">
                <div class="hidden lg:grid lg:grid-cols-3 gap-4 mb-8">
                    <button type="button" data-section="positions" data-action="switch-section" class="stat-card card-interactive group text-left" id="statPositions">
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Positions</p>
                        <p class="text-4xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white mt-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300" id="statPositionsCount"><?= number_format($positionsCount) ?></p>
                        <p class="view-hint opacity-0 max-h-0 overflow-hidden group-hover:opacity-100 group-hover:max-h-8 transition-all duration-300 text-sm font-semibold text-blue-600 dark:text-blue-400">View</p>
                    </button>
                    <button type="button" data-section="candidates" data-action="switch-section" class="stat-card card-interactive group text-left" id="statCandidates">
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Candidates</p>
                        <p class="text-4xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white mt-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300" id="statCandidatesCount"><?= number_format($candidatesCount) ?></p>
                        <p class="view-hint opacity-0 max-h-0 overflow-hidden group-hover:opacity-100 group-hover:max-h-8 transition-all duration-300 text-sm font-semibold text-blue-600 dark:text-blue-400">View</p>
                    </button>
                    <button type="button" data-section="partylists" data-action="switch-section" class="stat-card card-interactive group text-left" id="statPartylists">
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Partylists</p>
                        <p class="text-4xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white mt-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300" id="statPartylistsCount"><?= number_format($partylistsCount) ?></p>
                        <p class="view-hint opacity-0 max-h-0 overflow-hidden group-hover:opacity-100 group-hover:max-h-8 transition-all duration-300 text-sm font-semibold text-blue-600 dark:text-blue-400">View</p>
                    </button>
                </div>

                <div id="welcomeScreen" class="flex flex-col items-center justify-center py-8 lg:py-12">
                    <div class="w-[90%] h-[90%] flex overflow-hidden">
                        <img 
                            src="<?= APP_URL ?>/logo/covers1.jpg" 
                            alt="SOFTNET Logo" 
                            class="w-full h-full object-contain"
                        >
                    </div>
                </div>

                <div id="sectionContent" class="hidden">
                    <div id="positionsSection" class="section-panel hidden">
                        <div class="card">
                            <div class="flex flex-col sm:flex-row gap-3 mb-4"><div class="flex-1 w-full"><input type="search" id="positionSearch" placeholder="Search positions..." autocomplete="off" class="input-field w-full"></div><button class="bulk-delete-positions btn-secondary text-sm hidden w-full sm:w-auto">Delete Selected</button></div>
                            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="thead-desktop"><tr class="border-b border-zinc-200/60 dark:border-zinc-800/60"><?php if ($canEditInfo): ?><th class="px-4 py-3 text-left"><input type="checkbox" class="select-all-positions rounded"></th><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400 w-10"></th><?php endif; ?><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Name</th><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Description</th><?php if ($canEditInfo): ?><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Actions</th><?php endif; ?></tr></thead><tbody id="positionsTableBody"><tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">Loading...</td></tr></tbody></table></div>
                            <div class="flex flex-col items-start mt-4 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 gap-2">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400" id="positionsInfo"></p>
                                <div class="flex gap-2 flex-wrap" id="positionsPages"></div>
                            </div>
                        </div>
                    </div>

                    <div id="candidatesSection" class="section-panel hidden">
                        <div class="card">
                            <div class="flex flex-col sm:flex-row gap-3 mb-4"><div class="flex-1 w-full"><input type="search" id="candidateSearch" placeholder="Search candidates..." autocomplete="off" class="input-field w-full"></div><button class="bulk-delete-candidates btn-secondary text-sm hidden w-full sm:w-auto">Delete Selected</button></div>
                            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="thead-desktop"><tr class="border-b border-zinc-200/60 dark:border-zinc-800/60"><?php if ($canEditInfo): ?><th class="px-4 py-3 text-left"><input type="checkbox" class="select-all-candidates rounded"></th><?php endif; ?><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Image</th><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Name</th><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Position</th><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Partylist</th><?php if ($canEditInfo): ?><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Actions</th><?php endif; ?></tr></thead><tbody id="candidatesTableBody"><tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">Loading...</td></tr></tbody></table></div>
                            <div class="flex flex-col items-start mt-4 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 gap-2">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400" id="candidatesInfo"></p>
                                <div class="flex gap-2 flex-wrap" id="candidatesPages"></div>
                            </div>
                        </div>
                    </div>

                    <div id="partylistsSection" class="section-panel hidden">
                        <div class="card">
                            <div class="flex flex-col sm:flex-row gap-3 mb-4"><div class="flex-1 w-full"><input type="search" id="partylistSearch" placeholder="Search partylists..." autocomplete="off" class="input-field w-full"></div><button class="bulk-delete-partylists btn-secondary text-sm hidden w-full sm:w-auto">Delete Selected</button></div>
                            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="thead-desktop"><tr class="border-b border-zinc-200/60 dark:border-zinc-800/60"><?php if($canEditInfo):?><th class="px-4 py-3 text-left"><input type="checkbox" class="select-all-partylists rounded"></th><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400 w-10"></th><?php endif; ?><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Name</th><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Description</th><?php if ($canEditInfo): ?><th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Actions</th><?php endif; ?></tr></thead><tbody id="partylistsTableBody"><tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">Loading...</td></tr></tbody></table></div>
                            <div class="flex flex-col items-start mt-4 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 gap-2">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400" id="partylistsInfo"></p>
                                <div class="flex gap-2 flex-wrap" id="partylistsPages"></div>
                            </div>
                        </div>
                    </div>

                    <button id="dynamicAddBtn" class="fixed bottom-24 right-4 lg:bottom-8 lg:right-8 z-20 w-14 h-14 rounded-full bg-blue-600 text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/40 active:scale-95 transition-all duration-300 hidden items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>

                                      <div id="positionModal" class="fixed inset-0 z-50 hidden"><div id="positionModalBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div><div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md max-h-[90dvh] bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl shadow-zinc-500/20 ring-1 ring-zinc-200 dark:ring-zinc-800 flex flex-col sm:mx-0"><div class="shrink-0 px-6 pt-6 pb-4"><h3 id="positionModalTitle" class="text-lg font-bold text-zinc-900 dark:text-white">Add Position</h3></div><div class="flex-1 overflow-y-auto px-6"><form id="positionForm"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" id="posId" value=""><div class="mb-4"><label for="posName" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Name</label><input type="text" id="posName" name="name" autocomplete="off" required class="input-field"></div><div class="mb-4"><label for="posDesc" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Description</label><textarea id="posDesc" name="description" rows="2" class="input-field"></textarea></div></form></div><div class="shrink-0 px-6 pb-6 pt-2 flex flex-col sm:flex-row justify-end gap-3"><button type="button" id="closePositionModalBtn" class="btn-secondary w-full sm:w-auto">Cancel</button><button type="submit" form="positionForm" class="btn-primary w-full sm:w-auto">Save</button></div></div></div>

                    <div id="candidateModal" class="fixed inset-0 z-50 hidden"><div id="candidateModalBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div><div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full sm:w-[80%] lg:w-[60%] max-h-[90dvh] bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl shadow-zinc-500/20 ring-1 ring-zinc-200 dark:ring-zinc-800 flex flex-col"><div class="shrink-0 px-4 sm:px-6 pt-6 pb-4"><h3 id="candidateModalTitle" class="text-lg font-bold text-zinc-900 dark:text-white">Add Candidate</h3></div><div class="flex-1 overflow-y-auto px-4 sm:px-6"><form id="candidateForm" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" id="candId" value=""><input type="hidden" name="remove_image" id="candRemoveImage" value="0"><div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div class="mb-4"><label for="candName" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Name</label><input type="text" id="candName" name="name" autocomplete="off" required class="input-field"></div><div class="mb-4"><label for="candPosition" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Position</label><select id="candPosition" name="position_id" required class="input-field"></select></div><div class="mb-4"><label for="candPartylist" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Partylist</label><select id="candPartylist" name="partylist_id" class="input-field"><option value="">None</option></select></div><div class="mb-4"><label for="candImage" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Image</label><input type="file" id="candImage" name="image" accept=".jpg,.jpeg,.png,.webp" class="input-field"><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Max file size: 2MB (JPG, PNG, or WebP)</p><div id="candImagePreviewContainer" class="mt-2 hidden"><img id="candImagePreview" src="" class="w-20 h-20 rounded-full object-cover ring-1 ring-zinc-200/60 dark:ring-zinc-700/60"><button type="button" id="candRemoveImageBtn" class="mt-2 text-xs font-semibold text-red-500 hover:text-red-400 transition">Remove Image</button></div></div></div><div class="mb-4"><label for="candPlatform" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Platform</label><textarea id="candPlatform" name="platform" rows="2" class="input-field"></textarea></div><div class="mb-4"><label class="flex items-center gap-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300 cursor-pointer"><input type="checkbox" id="candDefaultWinner" name="is_default_winner" value="1" class="rounded"><span>Set as Default Winner (candidate withdrew, wins this position automatically)</span></label></div></form></div><div class="shrink-0 px-4 sm:px-6 pb-6 pt-2 flex flex-col sm:flex-row justify-end gap-3"><button type="button" id="closeCandidateModalBtn" class="btn-secondary w-full sm:w-auto">Cancel</button><button type="submit" form="candidateForm" class="btn-primary w-full sm:w-auto">Save</button></div></div></div>

                    <div id="partylistModal" class="fixed inset-0 z-50 hidden"><div id="partylistModalBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div><div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md max-h-[90dvh] bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl shadow-zinc-500/20 ring-1 ring-zinc-200 dark:ring-zinc-800 flex flex-col sm:mx-0"><div class="shrink-0 px-6 pt-6 pb-4"><h3 id="partylistModalTitle" class="text-lg font-bold text-zinc-900 dark:text-white">Add Partylist</h3></div><div class="flex-1 overflow-y-auto px-6"><form id="partylistForm"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" id="plId" value=""><div class="mb-4"><label for="plName" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Name</label><input type="text" id="plName" autocomplete="off" name="name" required class="input-field"></div><div class="mb-4"><label for="plDesc" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Description</label><textarea id="plDesc" name="description" rows="2" class="input-field"></textarea></div><div class="mb-4"><label for="plBadgeColor" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Badge Color</label><div class="flex items-center gap-3"><input type="color" id="plBadgeColor" name="badge_color" value="#1e40af" class="h-10 w-14 rounded-lg cursor-pointer border-0 p-0 bg-transparent"><input type="text" id="plBadgeColorText" value="#1e40af" class="input-field w-32" readonly></div><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Custom background color for partylist badge</p></div><div class="mb-4"><label for="plBadgeTextColor" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Badge Text Color</label><div class="flex items-center gap-3"><input type="color" id="plBadgeTextColor" name="badge_text_color" value="#ffffff" class="h-10 w-14 rounded-lg cursor-pointer border-0 p-0 bg-transparent"><input type="text" id="plBadgeTextColorText" value="#ffffff" class="input-field w-32" readonly></div><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Text color for partylist badge (use light for dark backgrounds)</p></div></form></div><div class="shrink-0 px-6 pb-6 pt-2 flex flex-col sm:flex-row justify-end gap-3"><button type="button" id="closePartylistModalBtn" class="btn-secondary w-full sm:w-auto">Cancel</button><button type="submit" form="partylistForm" class="btn-primary w-full sm:w-auto">Save</button></div></div></div>
                </div>
            </div>
        </main>

       <nav class="lg:hidden fixed bottom-0 left-0 right-0 glass-strong border-t border-zinc-200/60 dark:border-zinc-800/60 z-30"><div class="flex justify-around py-1.5"><button data-action="switch-section" data-section="positions" class="flex flex-col items-center px-1.5 py-0.5 text-[10px] text-zinc-500 dark:text-zinc-400 font-medium"><svg class="w-4 h-4 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg><span>Positions</span><span class="text-[9px] font-bold" id="mobilePositionsCount">0</span></button><button data-action="switch-section" data-section="candidates" class="flex flex-col items-center px-1.5 py-0.5 text-[10px] text-zinc-500 dark:text-zinc-400 font-medium"><svg class="w-4 h-4 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span>Candidates</span><span class="text-[9px] font-bold" id="mobileCandidatesCount">0</span></button><button data-action="switch-section" data-section="partylists" class="flex flex-col items-center px-1.5 py-0.5 text-[10px] text-zinc-500 dark:text-zinc-400 font-medium"><svg class="w-4 h-4 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg><span>Partylists</span><span class="text-[9px] font-bold" id="mobilePartylistsCount">0</span></button></div></nav>
    </div>

    <div id="votingControlsModal" class="fixed inset-0 z-50 hidden">
        <div id="votingControlsBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[80%] sm:max-w-md bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl shadow-zinc-500/20 ring-1 ring-zinc-200 dark:ring-zinc-800 flex flex-col max-h-[85dvh]">
            <div class="flex items-center justify-between px-6 pt-6 pb-4 shrink-0">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Voting Controls</h3>
                <button id="closeVotingControlsBtn" class="p-1.5 rounded-2xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300">
                    <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 pb-6 space-y-4">
                <div class="flex items-center justify-between p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">Login</span>
                    <button type="button" id="loginToggleSwitch" class="toggle-switch" data-type="login" aria-pressed="false">
                        <span class="toggle-knob"></span>
                    </button>
                </div>
                <div class="flex items-center justify-between p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">Voting</span>
                    <button type="button" id="voteToggleSwitch" class="toggle-switch" data-type="vote" aria-pressed="false">
                        <span class="toggle-knob"></span>
                    </button>
                </div>
                <div class="flex items-center justify-between p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">Guest Mode</span>
                    <button type="button" id="guestToggleSwitch" class="toggle-switch" data-type="guest" aria-pressed="false">
                        <span class="toggle-knob"></span>
                    </button>
                </div>
                <div class="flex items-center justify-between p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">Test Vote</span>
                    <button type="button" id="testToggleSwitch" class="toggle-switch" data-type="test" aria-pressed="false">
                        <span class="toggle-knob"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= htmlspecialchars($nonce) ?>">
window.SSC.appUrl = '<?= APP_URL ?>';
window.SSC.csrfToken = '<?= \App\Services\SessionService::getCSRFToken() ?>';
window.SSC.isSuperAdmin = <?= $isSuperAdmin ? 'true' : 'false' ?>;
window.SSC.canEditInfo = <?= ($canEditInfo ? 'true' : 'false') ?>;
window.SSC.canManageVote = <?= ($canManageVote ? 'true' : 'false') ?>;
</script>
<script nonce="<?= htmlspecialchars($nonce) ?>">
(function(){
    function sync(inp, txt, def){
        if (inp && txt) {
            inp.addEventListener('input', function(){ txt.value = inp.value; });
            txt.addEventListener('input', function(){ if(/^#[0-9A-Fa-f]{6}$/.test(txt.value)) inp.value = txt.value; });
        }
    }
    sync(document.getElementById('plBadgeColor'), document.getElementById('plBadgeColorText'), '#1e40af');
    sync(document.getElementById('plBadgeTextColor'), document.getElementById('plBadgeTextColorText'), '#ffffff');
})();
</script>
<script src="<?= \App\Services\AdminSecurityService::asset('js/admin/dashboard.js') ?>" nonce="<?= htmlspecialchars(\App\Services\AdminSecurityService::getNonce()) ?>"></script>
<?php
$pdo = \App\Core\Model::db();
$plRows = $pdo->query("SELECT name, badge_color, badge_text_color FROM partylists")->fetchAll();
if ($plRows):
?>
<style nonce="<?= htmlspecialchars($nonce ?? \App\Services\AdminSecurityService::getNonce()) ?>">
.pl-badge { background-color: var(--pl-color, #1e40af); color: var(--pl-text, #ffffff); }
.drag-handle { touch-action: none; user-select: none; }
.sortable-ghost { background-color: #dbeafe !important; opacity: 0.9; border: 2px dashed #60a5fa; }
.drag-ready { background-color: #bfdbfe !important; border: 2px solid #93c5fd; }
<?php foreach ($plRows as $pl): ?>
.pl-badge[data-partylist-badge="<?= htmlspecialchars($pl['name'], ENT_QUOTES) ?>"] { --pl-color: <?= htmlspecialchars($pl['badge_color'] ?: '#1e40af') ?>; --pl-text: <?= htmlspecialchars($pl['badge_text_color'] ?: '#ffffff') ?>; }
<?php endforeach; ?>
</style>
<?php endif; ?>