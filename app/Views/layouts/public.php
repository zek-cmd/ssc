<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/favicon.ico">
    
    <link href="<?= \App\Services\SecurityService::asset('local/css/montserrat-font.css') ?>" rel="stylesheet" nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>">
    <link href="<?= \App\Services\SecurityService::asset('css/tailwind.min.css') ?>" rel="stylesheet" nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>">

    <style nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>">
        [x-cloak] { display: none !important; }
        .assets-cached #pageLoader { display: none !important; }
    </style>

    <script nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>">
if (window.trustedTypes && trustedTypes.createPolicy) {
    trustedTypes.createPolicy('default', {
        createHTML: function(string) { return string; },
        createScript: function(string) { return string; },
        createScriptURL: function(string) { return string; }
    });
}
</script>

    <script nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>">
(function() {
    var cacheKey = 'ssc_assets_cached';
    var cached = sessionStorage.getItem(cacheKey);
    if (cached && (Date.now() - parseInt(cached)) < 3600000) {
        document.documentElement.classList.add('assets-cached');
    }
})();
</script>
    
    <script nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>">
        document.documentElement.classList.add('js');
        window.SSC = {
            appUrl: '<?= APP_URL ?>',
            sessionLifetime: <?= (int) SESSION_LIFETIME ?>,
            csrfToken: '<?= htmlspecialchars($csrfToken ?? '') ?>',
            nonce: '<?= htmlspecialchars($nonce) ?>',
            isAuthenticated: <?= \App\Services\SessionService::isAuthenticated() ? 'true' : 'false' ?>,
            turnstileSiteKey: '<?= TURNSTILE_SITE_KEY ?>',
            candidateImagesUrl: '<?= CANDIDATE_IMAGES_URL ?>',
            isOffline: <?= !empty($isOffline) ? 'true' : 'false' ?>
        };
    </script>
</head>
<body class="min-h-dvh bg-zinc-50 dark:bg-black text-zinc-900 dark:text-zinc-100 antialiased">
    <div id="pageLoader" class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-50 dark:bg-black transition-opacity duration-300">
        <div class="text-center">
            <img src="<?= APP_URL ?>/logo/softnet.png" alt="SOFTNET Logo" class="w-20 h-20 mx-auto mb-4 animate-pulse">
            <div class="flex items-center justify-center gap-1">
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce loader-dot-1"></div>
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce loader-dot-2"></div>
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce loader-dot-3"></div>
            </div>
        </div>
    </div>

    <main class="min-h-dvh">
        <?= $content ?>
    </main>
    
    <script src="<?= \App\Services\SecurityService::asset('js/layouts/public.js') ?>" nonce="<?= htmlspecialchars(\App\Services\SecurityService::getNonce()) ?>"></script>
</body>
</html>