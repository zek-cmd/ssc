<?php
$icons = [
    'dashboard' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    'results' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'accounts' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    'audit' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    'security' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
    'controls' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    'logout' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>',
];

// Inline script to prevent FOUC — runs before body renders
$sidebarAdminRole = \App\Services\SessionService::getAdminRole() ?? '';
$sidebarFoucScript = '
<script nonce="' . htmlspecialchars($nonce ?? '') . '">
(function() {
    var collapsed = sessionStorage.getItem("ssc_sidebar_collapsed");
    var role = "' . htmlspecialchars($sidebarAdminRole) . '";
    if (collapsed === "1" && role !== "admin") {
        document.documentElement.classList.add("sidebar-is-collapsed");
    }
})();
</script>
<style nonce="' . htmlspecialchars($nonce ?? '') . '">
    .sidebar-is-collapsed #adminSidebar {
        width: 4rem !important;
    }
    .sidebar-is-collapsed #adminSidebar .sidebar-expanded-item,
    .sidebar-is-collapsed #adminSidebar .sidebar-expanded-header,
    .sidebar-is-collapsed #adminSidebar .sidebar-logo,
    .sidebar-is-collapsed #adminSidebar .sidebar-label,
    .sidebar-is-collapsed #adminSidebar .sidebar-user-info,
    .sidebar-is-collapsed #adminSidebar .sidebar-toggle-switch {
        display: none !important;
    }
    .sidebar-is-collapsed #adminSidebar .sidebar-collapsed-item,
    .sidebar-is-collapsed #adminSidebar .sidebar-collapsed-header {
        display: flex !important;
    }
    .sidebar-is-collapsed #adminSidebar .sidebar-collapsed-item {
        justify-content: center !important;
    }
</style>
';

// Output the inline script + style — should be in <head>, but works here if header partial hasn't rendered yet
if (!defined('SIDEBAR_FOUC_OUTPUT')) {
    define('SIDEBAR_FOUC_OUTPUT', true);
    echo $sidebarFoucScript;
}
?>
<aside id="adminSidebar" class="hidden lg:flex lg:flex-col w-72 bg-white dark:bg-zinc-900 border-r border-zinc-200/60 dark:border-zinc-800/60 transition-all duration-300 ease-out">
    <div class="p-5 border-b border-zinc-200/60 dark:border-zinc-800/60 shrink-0">
        <div class="flex items-center justify-between sidebar-expanded-header">
            <div class="flex items-center space-x-3 min-w-0 sidebar-logo">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center overflow-hidden shrink-0">
                    <img src="<?= APP_URL ?>/logo/softnet.png" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div class="sidebar-header-text min-w-0">
                    <h1 class="text-sm font-bold tracking-tight text-zinc-900 dark:text-white truncate">Election Management</h1>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium"><?= htmlspecialchars($pageTitle) ?></p>
                </div>
            </div>
            <button id="sidebarCollapseBtn" class="p-1.5 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 shrink-0 ml-2">
                <svg id="sidebarCollapseIcon" class="w-4 h-4 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <svg id="sidebarExpandIcon" class="w-4 h-4 text-zinc-500 dark:text-zinc-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
        <div class="hidden items-center justify-center sidebar-collapsed-header">
            <button id="sidebarCollapseBtnCollapsed" class="p-1.5 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300">
                <svg id="sidebarExpandIconCollapsed" class="w-4 h-4 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto p-3 space-y-0.5">
        <?php foreach (($sidebarToggleItems ?? []) as $toggle): ?>
        <button id="<?= $toggle['id'] ?>" class="w-full flex items-center justify-between space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-expanded-item">
            <span class="flex items-center space-x-3 min-w-0">
                <?= $icons[$toggle['icon']] ?? '' ?>
                <span class="sidebar-label truncate"><?= htmlspecialchars($toggle['label']) ?></span>
            </span>
            <span class="toggle-switch <?= !empty($toggle['enabled']) ? 'toggle-on' : '' ?> sidebar-toggle-switch" aria-pressed="<?= !empty($toggle['enabled']) ? 'true' : 'false' ?>">
                <span class="toggle-knob"></span>
            </span>
        </button>
        <button id="<?= $toggle['id'] ?>Collapsed" class="w-full hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="<?= htmlspecialchars($toggle['label']) ?>">
            <?= $icons[$toggle['icon']] ?? '' ?>
        </button>
        <?php endforeach; ?>
        <?php foreach (($sidebarNavItems ?? []) as $item): ?>
        <a href="<?= $item['url'] ?>" class="sidebar-link nav-link flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-expanded-item">
            <?= $icons[$item['icon']] ?? '' ?>
            <span class="sidebar-label truncate"><?= htmlspecialchars($item['label']) ?></span>
        </a>
        <a href="<?= $item['url'] ?>" class="sidebar-link nav-link hidden items-center justify-center px-3 py-2.5 rounded-2xl text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300 sidebar-collapsed-item" title="<?= htmlspecialchars($item['label']) ?>">
            <?= $icons[$item['icon']] ?? '' ?>
        </a>
        <?php endforeach; ?>
        <?= $extraSidebarContent ?? '' ?>
        <?php if (!empty($extraSidebarNavHtml)): ?>
        <?= $extraSidebarNavHtml ?>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-zinc-200/60 dark:border-zinc-800/60 shrink-0">
        <div class="flex items-center space-x-3 sidebar-user-info mb-3">
            <div class="w-9 h-9 rounded-2xl bg-blue-600 shadow-lg shadow-blue-600/20 flex items-center justify-center text-white text-sm font-bold shrink-0"><?= strtoupper(substr($adminUsername, 0, 1)) ?></div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($adminUsername) ?></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium"><?= $isSuperAdmin ? 'Super Admin' : 'Admin' ?></p>
            </div>
        </div>
      <form method="POST" action="<?= APP_URL ?>/admin/logout">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Services\SessionService::getCSRFToken()) ?>">
    <button type="submit"  id="sidebarLogoutButton" class="flex items-center justify-center w-full px-3 py-2.5 text-sm font-semibold text-red-500 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-2xl transition-all duration-300">
        <?= $icons['logout'] ?>
        <span class="sidebar-label ml-2">Logout</span>
    </button>
</form>
    </div>
</aside>