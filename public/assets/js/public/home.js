// home.js
// Homepage - Vote cooldown timer
(function() {
    var cooldownRemaining = window.SSC.cooldownRemaining || 0;
    var cooldownTimerInterval = null;

    function startCooldownTimer() {
        var cooldownTimerEl = document.getElementById('cooldownTimer');
        var cooldownProgressEl = document.getElementById('cooldownProgress');
        var cooldownSection = document.getElementById('cooldownSection');
        var totalCooldown = cooldownRemaining;
        var secondsLeft = cooldownRemaining;

        if (!cooldownTimerEl || secondsLeft <= 0) {
            return;
        }

        if (cooldownProgressEl) {
            cooldownProgressEl.style.width = (secondsLeft / totalCooldown) * 100 + '%';
        }

        function updateCooldownDisplay() {
            secondsLeft--;
            cooldownTimerEl.textContent = secondsLeft;

            if (cooldownProgressEl && totalCooldown > 0) {
                var progressPercent = (secondsLeft / totalCooldown) * 100;
                cooldownProgressEl.style.width = progressPercent + '%';
            }

            if (secondsLeft <= 0) {
                clearInterval(cooldownTimerInterval);
                cooldownTimerInterval = null;
                
                if (cooldownSection) {
                    cooldownSection.innerHTML = `
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
                        <a href="${window.SSC.appUrl}/vote" class="btn-primary text-lg px-10 py-4 group">
                            <span>Proceed to Vote</span>
                            <svg class="ml-3 w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                    `;
                }
            }
        }

        cooldownTimerInterval = setInterval(updateCooldownDisplay, 1000);
    }

    if (cooldownRemaining > 0) {
        startCooldownTimer();
    }

    window.addEventListener('beforeunload', function() {
        if (cooldownTimerInterval) {
            clearInterval(cooldownTimerInterval);
            cooldownTimerInterval = null;
        }
    });
})();