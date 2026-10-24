<div class="min-h-dvh bg-zinc-50 dark:bg-zinc-950"> <div class="min-h-dvh flex items-center justify-center px-4 sm:px-6 lg:px-8">

    <div class="w-full max-w-5xl">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center justify-items-center">

            <div class="text-center">
                <img
                    src="<?= APP_URL ?>/logo/softnet.png"
                    alt="SOFTNET Logo"
                    class="w-24 h-24 mx-auto mb-4"
                >

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-[-0.04em] text-zinc-900 dark:text-white">
                    Election Management
                </h1>

                <p class="mt-3 text-sm lg:text-base text-zinc-500 dark:text-zinc-400 font-medium">
                    SOFTNET Information Technology Center, Inc.
                </p>
            </div>

            <div class="w-full max-w-sm">
                <div id="loginError" class="hidden alert-error mb-6" role="alert"></div>

                <form
                    id="adminLoginForm"
                    class="card-glass p-8 space-y-5"
                    method="POST"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken) ?>"
                    >

                    <div>
                        <label
                            for="username"
                            class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2"
                        >
                            Username
                        </label>

                        <input
                            id="username"
                            name="username"
                            type="text"
                            required
                            autocomplete="off"
                            class="input-field"
                            placeholder="Enter username"
                        >
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2"
                        >
                            Password
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="input-field"
                            placeholder="Enter password"
                        >
                    </div>

                    <?php if (!$isOffline): ?>
                        <div
                            class="cf-turnstile"
                            data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>"
                            data-theme="auto"
                        ></div>
                    <?php endif; ?>

                    <button
                        type="submit"
                        id="loginButton"
                        class="btn-primary w-full"
                    >
                        Sign in
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>

</div>

<?php if (!$isOffline): ?>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer ></script>

<?php endif; ?>

<script src="<?= \App\Services\AdminSecurityService::asset('js/admin/login.js') ?>" nonce="<?= htmlspecialchars(\App\Services\AdminSecurityService::getNonce()) ?>" ></script>