<header class="glass-strong sticky top-0 z-30 border-b border-zinc-200/60 dark:border-zinc-800/60">
    <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-3 sm:py-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
              <div class="w-8 h-8 flex items-center justify-center overflow-hidden">
    <img src="<?= APP_URL ?>/logo/softnet.png" alt="Logo" class="w-full h-full object-contain">
</div>
                <div>
                    <h1 class="text-sm sm:text-base font-bold tracking-tight text-zinc-900 dark:text-white">Election Management</h1>
                    <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 font-medium">SOFTNET Information Technology Center, Inc.</p>
                </div>
            </div>
<?php if (!empty($headerBackUrl)): ?>
            <a href="<?= htmlspecialchars($headerBackUrl) ?>" class="inline-flex items-center gap-2 px-3 py-2 sm:px-4 rounded-2xl text-xs sm:text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white/70 dark:bg-zinc-800/70 ring-1 ring-zinc-200 dark:ring-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-all duration-300 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Home</span>
            </a>
<?php endif; ?>
        </div>
    </div>
</header>