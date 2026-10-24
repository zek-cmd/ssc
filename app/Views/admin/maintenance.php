<?php
$isOffline = defined('TURNSTILE_OFFLINE') && TURNSTILE_OFFLINE;
?>

<div class="min-h-dvh bg-zinc-50 dark:bg-zinc-950">
    <div class="min-h-dvh flex items-center justify-center px-4 sm:px-6 lg:px-8">

        <div class="w-full max-w-5xl">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center justify-items-center">

                <div class="text-center">
                    <img
                        src="<?= APP_URL ?>/logo/softnet.png"
                        alt="SOFTNET Logo"
                        class="w-24 h-24 mx-auto mb-4"
                    >

                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white">
                        Maintenance Mode
                    </h1>

                    <p class="mt-3 text-sm lg:text-base text-zinc-500 dark:text-zinc-400 font-medium">
                        Site status: 
                        <?php if ($siteEnabled): ?>
                            <span class="badge-green">UP</span>
                        <?php else: ?>
                            <span class="badge-red">DOWN</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="w-full max-w-sm">
                    <div class="card-glass p-8 space-y-6 text-center">

                        <button type="button" id="toggleSiteBtn" class="btn-primary w-full">
                            <?= $siteEnabled ? 'Take Site DOWN' : 'Bring Site UP' ?>
                        </button>

                        <?php if ($siteEnabled): ?>
                            <a href="<?= APP_URL ?>/admin/dashboard" class="btn-secondary w-full inline-flex">
                                Dashboard
                            </a>
                        <?php endif; ?>

                       <form method="POST" action="<?= APP_URL ?>/admin/logout">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Services\SessionService::getCSRFToken()) ?>">
    <button type="submit" class="btn-secondary w-full">
        Logout
    </button>
</form>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<script nonce="<?= htmlspecialchars($nonce) ?>">
window.SSC.appUrl = '<?= APP_URL ?>';
window.SSC.csrfToken = '<?= htmlspecialchars($csrfToken) ?>';
window.SSC.siteEnabled = <?= $siteEnabled ? 'true' : 'false' ?>;
</script>

<script src="<?= \App\Services\AdminSecurityService::asset('js/admin/maintenance.js') ?>" nonce="<?= htmlspecialchars(\App\Services\AdminSecurityService::getNonce()) ?>"></script>