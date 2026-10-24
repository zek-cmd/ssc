<?php
$icons = [
    'dashboard' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
    'results' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
    'accounts' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
    'audit' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    'security' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
    'controls' => '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
];
?>
<header class="lg:hidden glass-strong border-b border-zinc-200/60 dark:border-zinc-800/60 px-4 py-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
         <div class="w-8 h-8 flex items-center justify-center overflow-hidden">
    <img src="<?= APP_URL ?>/logo/softnet.png" alt="Logo" class="w-full h-full object-contain">
</div>
            <div><h1 class="text-sm font-bold tracking-tight text-zinc-900 dark:text-white">Election Management</h1>
        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium"><?= htmlspecialchars($pageTitle) ?></p>
        </div>
        </div>
        <div class="flex items-center space-x-3">
            <button id="mobileMenuBtn" class="p-2 rounded-2xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300">
                <svg id="menuIconOpen" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg id="menuIconClose" class="w-5 h-5 text-zinc-600 dark:text-zinc-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
    <div id="mobileMenu" class="hidden mt-3 border-t border-zinc-200/60 dark:border-zinc-800/60 max-h-0 opacity-0 overflow-hidden transition-all duration-300 ease-out">
    <div class="pt-3 space-y-0.5 max-h-[calc(100dvh-8rem)] overflow-y-auto">
            <?php if ($pageTitle !== 'Dashboard'): ?>
            <a href="<?= APP_URL ?>/admin/dashboard" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800"><?= $icons['dashboard'] ?><span>Dashboard</span></a>
            <?php endif; ?>
            <?php foreach (($mobileNavItems ?? []) as $item): ?>
            <a href="<?= $item['url'] ?>" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800"><?= $icons[$item['icon']] ?? '' ?><span><?= htmlspecialchars($item['label']) ?></span></a>
            <?php endforeach; ?>
            <?= $extraMobileContent ?? '' ?>
            <?php if (!empty($extraMobileNavHtml)): ?>
            <?= $extraMobileNavHtml ?>
            <?php endif; ?>
           <form method="POST" action="<?= APP_URL ?>/admin/logout">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Services\SessionService::getCSRFToken()) ?>">
    <button type="submit" class="flex items-center space-x-3 px-3 py-2.5 rounded-2xl text-sm font-semibold text-red-500 hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 ml-px w-full">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        <span class="truncate">Logout</span>
    </button>
</form>
        </div>
    </div>
</header>