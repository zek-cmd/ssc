<div class="min-h-dvh flex flex-col bg-zinc-50 dark:bg-zinc-950">
    <?php require APP_PATH . '/Views/public/partials/header.php'; ?>

    <main class="flex-1 px-0 py-6 sm:py-8">
        <div class="w-full">



<?php if (!$isOffline): ?>
            <div id="turnstileGate" class="min-h-[60vh] flex items-center justify-center px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-sm text-center">
                    <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-theme="auto" data-callback="onTurnstileSuccess"></div>
                </div>
            </div>
            <?php endif; ?>

            <div id="step1" class="hidden px-2 sm:px-4 lg:px-6 pb-24 sm:pb-28">
                <div id="positionsContainer" class="space-y-8 sm:space-y-10 w-full"></div>
                <div id="stickyReviewBtn" class="fixed bottom-0 left-0 right-0 z-20 px-3 sm:px-6 lg:px-8 py-3 sm:py-4 hidden">
                    <div class="flex justify-center">
                        <button type="button" id="reviewSelectionsBtn" class="btn-primary px-6 text-base sm:text-lg py-3 sm:py-4 shadow-2xl shadow-blue-600/30">
                            Click to Proceed
                        </button>
                    </div>
                </div>
            </div>

      
            <div id="selectionChip" class="fixed bottom-32 md:bottom-24 left-3 md:left-6 z-10 hidden opacity-0 transition-all duration-300">
                <div class="flex items-center gap-1.5 md:gap-2 px-3 py-1.5 md:px-4 md:py-2 rounded-full bg-white/90 dark:bg-zinc-900/90 backdrop-blur ring-1 ring-zinc-200 dark:ring-zinc-700 shadow-lg shadow-zinc-500/20">
                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span id="selectionChipText" class="text-xs md:text-sm font-semibold text-zinc-900 dark:text-white">0/12 Selected</span>
                </div>
            </div>

            <div id="step2" class="hidden px-4 sm:px-6 lg:px-8">
                <div class="w-full max-w-2xl lg:max-w-6xl mx-auto">

<?php if ($isTestVote): ?>
                    <div class="alert-warning mb-4 sm:mb-6" id="testVoteBanner">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="text-sm sm:text-lg font-bold">
                                THIS IS A TEST VOTE — This will be counted in official results.
                            </p>
                        </div>
                    </div>
<?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">

<?php if (!$isTestVote): ?>
                        <div class="flex flex-col gap-4 sm:gap-6">
                            <div class="card">
                                <label for="nameInput" class="block text-sm sm:text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Full Name
                                </label>
                                <input type="text" id="nameInput" name="name" required
                                       class="input-field text-sm sm:text-lg"
                                       placeholder="Enter your Name, (First Name, Middle Initials, Last Name)"
                                       autocomplete="off">
                            </div>

                            <div class="card">
                                <label for="sectionSelect" class="block text-sm sm:text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Section
                                </label>
                                <div class="relative">
                                    <input type="text" id="sectionInput" readonly placeholder="Tap to pick your section (e.g. IT 101)" autocomplete="off" role="combobox" aria-haspopup="dialog" aria-expanded="false" class="input-field cursor-pointer relative bg-transparent pr-10 text-sm sm:text-lg">
                                    <button type="button" id="sectionToggle" aria-label="Show sections" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-zinc-500 dark:text-zinc-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </div>
                                <p id="sectionWarning" class="hidden mt-2 text-sm sm:text-lg font-semibold text-red-600 dark:text-red-400">Invalid section name</p>
                                <datalist id="sectionList">
<?php foreach ($sections as $sec): ?>
                                    <option value="<?= htmlspecialchars($sec['name']) ?>" data-id="<?= (int)$sec['id'] ?>"></option>
<?php endforeach; ?>
                                </datalist>
                                <input type="hidden" id="sectionSelect" name="section_id" value="">
                            </div>

                            <div class="card">
                                <label for="emailInput" class="block text-sm sm:text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Email Address
                                </label>
                                <input type="email" id="emailInput" name="email" required
                                       class="input-field text-sm sm:text-lg"
                                       placeholder="Enter email to receive vote receipt"
                                       autocomplete="off">
                                <p class="mt-2 text-sm sm:text-lg font-semibold text-amber-600 dark:text-amber-400">
                                    ⚠️ Please make sure you provide a valid and active email address.
                                </p>
                            </div>
                        </div>
<?php endif; ?>

                        <div class="flex flex-col gap-4 sm:gap-6 <?= $isTestVote ? 'lg:col-span-2 max-w-2xl mx-auto w-full' : '' ?>">
                            <div class="flex flex-col gap-3 sm:gap-4">
                                <button type="button" id="viewSelectionsBtn"
                                        class="btn-secondary w-full px-6 sm:px-8 py-2.5 sm:py-3 text-sm sm:text-lg">
                                    Review Selections
                                </button>
                                <button type="button" id="submitVoteBtn"
                                        class="btn-primary w-full px-6 sm:px-8 py-2.5 sm:py-3 text-sm sm:text-lg font-bold hidden opacity-0 translate-x-4 transition-all duration-200 ease-out">
                                    Submit Vote
                                </button>
                            </div>

                            <div class="alert-warning" id="confirmWarning">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" id="confirmVoteCheckbox"
                                           class="mt-1 h-5 w-5 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-400 dark:bg-zinc-800 cursor-pointer">
                                    <label for="confirmVoteCheckbox" class="text-sm sm:text-lg font-semibold cursor-pointer select-none">
                                        ⚠️ I CONFIRM that I have reviewed my selections and understand that once submitted, my vote is FINAL and changes will NOT be allowed.
                                    </label>
                                </div>
                                <p id="confirmCountdown" class="mt-2 text-sm sm:text-lg font-bold text-blue-600 dark:text-blue-400 hidden"></p>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

       
            <div id="sectionModal" class="fixed inset-0 z-[60] hidden flex items-end sm:items-center justify-center sm:p-4">
                <div id="sectionModalBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div>
                <div class="relative flex flex-col w-full sm:max-w-md max-h-[85dvh] min-h-0 bg-white dark:bg-zinc-900 rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-200/60 dark:border-zinc-800/60 shrink-0">
                        <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">Select your Section</h3>
                        <button type="button" id="sectionModalClose" aria-label="Close" class="p-1.5 rounded-2xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300">
                            <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="relative px-5 pt-3 shrink-0">
                        <div id="sectionModalGhost" aria-hidden="true" class="input-field pointer-events-none absolute inset-x-5 top-3 overflow-hidden whitespace-pre text-sm sm:text-base"><span id="sectionModalGhostTyped" class="text-transparent"></span><span id="sectionModalGhostRest" class="text-zinc-400 dark:text-zinc-500"></span></div>
                        <input type="search" id="sectionModalSearch" placeholder="Type to search (e.g. IT 101)" autocomplete="off" class="input-field relative bg-transparent text-sm sm:text-base">
                    </div>
                    <div id="sectionModalList" role="listbox" class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-2 py-2"></div>
                    <p id="sectionModalEmpty" class="hidden px-5 pb-4 text-sm text-zinc-500">No section found</p>
                </div>
            </div>

            <div id="viewSelectionsModal" class="fixed inset-0 z-50 hidden">
                <div id="viewSelectionsBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div>
                <div class="absolute inset-0 w-full h-full bg-white dark:bg-zinc-900 flex flex-col">
                    <div class="flex items-center justify-between px-4 py-3 sm:px-6 sm:py-4 border-b border-zinc-200/60 dark:border-zinc-800/60 shrink-0">
                        <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">Your Selections</h3>
                        <button id="closeViewSelectionsBtn" class="p-1.5 rounded-2xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-300">
                            <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div id="viewSelectionsContent" class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-6 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4"></div>
                    <div class="px-4 py-3 sm:px-6 sm:py-4 border-t border-zinc-200/60 dark:border-zinc-800/60 shrink-0 flex flex-col sm:flex-row gap-2 sm:gap-3">
                        <button type="button" id="modalEditSelectionsBtn" class="btn-secondary flex-1 text-sm sm:text-lg py-2.5 sm:py-3">
                            Edit Selections
                        </button>
                        <button type="button" id="modalOkBtn" class="btn-primary flex-1 text-sm sm:text-lg py-2.5 sm:py-3">
                            OK
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </main>

</div>

<?php if (!$isOffline): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<script nonce="<?= htmlspecialchars($nonce) ?>">
window.SSC.currentStep = 'select';
window.SSC.initialSelections = <?= json_encode($selections) ?>;
window.SSC.isTestVote = <?= $isTestVote ? 'true' : 'false' ?>;
</script>
<script src="<?= \App\Services\SecurityService::asset('js/public/vote.js') ?>" nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>"></script>
