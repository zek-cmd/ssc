<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= APP_URL ?>/favicon.ico">
    <link href="<?= \App\Services\AdminSecurityService::asset('local/css/montserrat-font.css') ?>" rel="stylesheet" nonce="<?= htmlspecialchars($nonce) ?>">
    <link href="<?= \App\Services\AdminSecurityService::asset('css/tailwind.min.css') ?>" rel="stylesheet" nonce="<?= htmlspecialchars($nonce) ?>">

    <style nonce="<?= htmlspecialchars($nonce) ?>">
        [x-cloak] { display: none !important; }
        .sidebar-link.active {
            @apply bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border-r-2 border-blue-600;
        }
        .assets-cached #pageLoader { display: none !important; }

        /* SweetAlert2 - OS theming */
        .swal2-popup {
            background: #ffffff;
            color: #18181b;
        }

        .swal2-title,
        .swal2-html-container {
            color: #18181b;
        }

        .swal2-input,
        .swal2-textarea,
        .swal2-select {
            background: #ffffff;
            color: #18181b;
            border-color: #d4d4d8;
        }

        .swal2-input::placeholder,
        .swal2-textarea::placeholder {
            color: #71717a;
        }

        @media (prefers-color-scheme: dark) {
            .swal2-popup {
                background: #18181b;
                color: #f4f4f5;
            }

            .swal2-title,
            .swal2-html-container {
                color: #f4f4f5;
            }

            .swal2-input,
            .swal2-textarea,
            .swal2-select {
                background: #27272a;
                color: #f4f4f5;
                border-color: #3f3f46;
            }

            .swal2-input::placeholder,
            .swal2-textarea::placeholder {
                color: #a1a1aa;
            }

            .swal2-validation-message {
                background: #27272a;
                color: #f4f4f5;
            }
        }
    </style>

    <script nonce="<?= htmlspecialchars($nonce) ?>">
if (window.trustedTypes && trustedTypes.createPolicy) {
    trustedTypes.createPolicy('default', {
        createHTML: function(string) { return string; },
        createScript: function(string) { return string; },
        createScriptURL: function(string) { return string; }
    });
}
    </script>

    <script nonce="<?= htmlspecialchars($nonce) ?>">
(function() {
    var cacheKey = 'ssc_admin_assets_cached';
    var cached = sessionStorage.getItem(cacheKey);
    if (cached && (Date.now() - parseInt(cached)) < 3600000) {
        document.documentElement.classList.add('assets-cached');
    }
})();
    </script>

    <script nonce="<?= htmlspecialchars($nonce) ?>">
        document.documentElement.classList.add('js');
        window.SSC = {
            appUrl: '<?= APP_URL ?>',
            sessionLifetime: <?= (int) SESSION_LIFETIME ?>,
            csrfToken: '<?= htmlspecialchars($csrfToken ?? '') ?>',
            nonce: '<?= htmlspecialchars($nonce) ?>',
            isAdmin: true,
            isAuthenticated: <?= \App\Services\SessionService::isAdminAuthenticated() ? 'true' : 'false' ?>,
            adminUsername: '<?= htmlspecialchars($adminUsername ?? '') ?>',
            adminRole: '<?= htmlspecialchars($adminRole ?? '') ?>',
            candidateImagesUrl: '<?= CANDIDATE_IMAGES_URL ?>',
            isSuperAdmin: <?= isset($isSuperAdmin) && $isSuperAdmin ? 'true' : 'false' ?>,
            isOffline: <?= !empty($isOffline) ? 'true' : 'false' ?>,
            canEditInfo: <?= isset($canEditInfo) && $canEditInfo ? 'true' : 'false' ?>,
            canManageVote: <?= isset($canManageVote) && $canManageVote ? 'true' : 'false' ?>,
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

    <?= $content ?>

    <link href="<?= \App\Services\AdminSecurityService::asset('local/css/sweetalert2-11.min.css') ?>" rel="stylesheet" nonce="<?= htmlspecialchars($nonce) ?>">
    <script src="<?= \App\Services\AdminSecurityService::asset('local/js/sweetalert2-11.min.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>
    <script src="<?= \App\Services\AdminSecurityService::asset('local/js/Sortable.min.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>
    <script src="<?= \App\Services\AdminSecurityService::asset('js/admin/partials/sidebar.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>
    <script src="<?= \App\Services\AdminSecurityService::asset('js/layouts/admin.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>
</body>
</html>