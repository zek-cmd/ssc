<div class="min-h-dvh flex flex-col bg-zinc-50 dark:bg-zinc-950">
    <?php require APP_PATH . '/Views/public/partials/header.php'; ?>

    <main class="flex-1 flex items-center justify-center px-4 py-12 sm:py-16">
        <div class="w-full max-w-5xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                
                
                <div class="flex flex-col items-center justify-center text-center animate-stagger">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-green-100 dark:bg-green-900/40 ring-1 ring-green-200 dark:ring-green-800/50 shadow-lg shadow-green-200/30 dark:shadow-green-900/30 mb-8">
                        <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white text-balance mb-3">
                        Vote Submitted
                    </h2>
                    <p class="text-zinc-500 dark:text-zinc-400 text-base font-medium">
                        Your vote has been recorded. Thank you for participating!
                    </p>
                </div>
                
             
                <div class="flex flex-col items-center justify-center text-center animate-stagger">
                    <div class="card mb-8 text-left w-full max-w-sm">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Voter Name</span>
                                <span class="text-sm font-semibold text-zinc-900 dark:text-white" id="displayVoterName">Loading...</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Voter Number</span>
                                <span class="text-sm font-semibold text-zinc-900 dark:text-white font-mono" id="displayVoterId">-</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400">
                                    Submitted
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert-info mb-8 w-full max-w-sm">
                        <p class="text-sm font-medium">
                            📧 A vote receipt has been sent to your email address. Please check your inbox (and spam folder) for confirmation.
                        </p>
                    </div>
                    
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Returning to homepage in <span id="countdown" class="font-bold text-zinc-900 dark:text-white">10</span> seconds...
                    </p>
                </div>
                
            </div>
        </div>
    </main>

</div>

<script nonce="<?= htmlspecialchars($nonce) ?>">
window.SSC.appUrl = '<?= APP_URL ?>';
window.SSC.voterId = '<?= htmlspecialchars($voterIdentifier ?? '') ?>';
window.SSC.redirectSeconds = 10;
</script>
<script src="<?= \App\Services\SecurityService::asset('js/public/success.js') ?>" nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>"></script>