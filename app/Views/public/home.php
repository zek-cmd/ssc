<div class="min-h-dvh flex flex-col bg-zinc-50 dark:bg-zinc-950">
    <?php require APP_PATH . '/Views/public/partials/header.php'; ?>

    <main class="flex-1 flex items-center justify-center px-4 py-12 sm:py-16">
        <div class="text-center max-w-lg mx-auto">
            <?php if ($votingEnabled && $cooldownRemaining > 0): ?>
                <div class="animate-stagger" id="cooldownSection">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-amber-100 dark:bg-amber-900/40 ring-1 ring-amber-200 dark:ring-amber-800/50 shadow-lg shadow-amber-200/30 dark:shadow-amber-900/30 mb-8">
                        <svg class="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white text-balance mb-3">
                        Please Wait
                    </h2>
                    <p class="text-zinc-500 dark:text-zinc-400 text-base font-medium mb-4">
                        Next voter will be available shortly.
                    </p>
                    <p class="text-zinc-500 dark:text-zinc-400 text-sm mb-10">
                        Time remaining: <span id="cooldownTimer" class="font-bold text-zinc-900 dark:text-white"><?= $cooldownRemaining ?></span> seconds
                    </p>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-800 rounded-full h-3 mb-10 overflow-hidden">
                        <div id="cooldownProgress" class="h-full bg-amber-500 rounded-full transition-all duration-1000" style="width: <?= ($cooldownRemaining / 60) * 100 ?>%;"></div>
                    </div>
                    <div id="proceedButtonContainer" class="hidden">
                        <a href="<?= APP_URL ?>/vote" class="btn-primary text-lg px-10 py-4 group">
                            <span>Proceed to Vote</span>
                            <svg class="ml-3 w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php elseif ($votingEnabled): ?>
                <div class="animate-stagger">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-blue-100 dark:bg-blue-900/40 ring-1 ring-blue-200 dark:ring-blue-800/50 shadow-lg shadow-blue-200/30 dark:shadow-blue-900/30 mb-8">
                        <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white text-balance mb-3">
                        Ready to Vote
                    </h2>
                    <p class="text-zinc-500 dark:text-zinc-400 text-base font-medium mb-10">
                        Cast your vote for the student council elections.
                    </p>
                    
                    <a href="<?= APP_URL ?>/vote" 
                       class="btn-primary text-lg px-10 py-4 group">
                        <span>Proceed to Vote</span>
                        <svg class="ml-3 w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
            <?php else: ?>
                <div class="animate-stagger">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-amber-100 dark:bg-amber-900/40 ring-1 ring-amber-200 dark:ring-amber-800/50 shadow-lg shadow-amber-200/30 dark:shadow-amber-900/30 mb-8">
                        <svg class="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white text-balance mb-3">
                        Voting is Currently Closed
                    </h2>
                    <p class="text-zinc-500 dark:text-zinc-400 text-base font-medium">
                        The election period is not active. Please check back later.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</div>

<script nonce="<?= htmlspecialchars($nonce) ?>">
window.SSC.appUrl = '<?= APP_URL ?>';
window.SSC.csrfToken = '<?= \App\Services\SessionService::getCSRFToken() ?>';
window.SSC.cooldownRemaining = <?= (int)$cooldownRemaining ?>;
</script>
<script src="<?= \App\Services\SecurityService::asset('js/public/home.js') ?>" nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>"></script>