// Admin Login with Turnstile / Offline Bypass
(function() {
var form = document.getElementById('adminLoginForm');
if (!form) return;

var errorDiv = document.getElementById('loginError');
var loginButton = document.getElementById('loginButton');
var isOffline = window.SSC && window.SSC.isOffline === true;

form.addEventListener('submit', async function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    var turnstileInput = document.querySelector(
        '[name="cf-turnstile-response"]'
    );

    // Require Turnstile only when offline bypass is disabled
    if (
        !isOffline &&
        (!turnstileInput || !turnstileInput.value)
    ) {
        if (errorDiv) {
            errorDiv.textContent =
                'Please complete the verification challenge';
            errorDiv.classList.remove('hidden');
        }
        return;
    }

    if (errorDiv) {
        errorDiv.classList.add('hidden');
    }

    if (loginButton) {
        loginButton.disabled = true;
        loginButton.innerHTML = 'Signing in...';
    }

    try {
        var response = await fetch(
            window.SSC.appUrl + '/admin/login',
            {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );

        var data = await response.json();

        if (data.success) {
            window.location.href = data.redirect;
            return;
        }

        if (errorDiv) {
            errorDiv.textContent =
                data.error || 'Unknown error';
            errorDiv.classList.remove('hidden');
        }

        // Reset Turnstile only when enabled
        if (
            !isOffline &&
            typeof turnstile !== 'undefined'
        ) {
            turnstile.reset();
        }

    } catch (error) {
        if (errorDiv) {
            errorDiv.textContent =
                'An error occurred. Please try again.';
            errorDiv.classList.remove('hidden');
        }

        if (
            !isOffline &&
            typeof turnstile !== 'undefined'
        ) {
            turnstile.reset();
        }

    } finally {
        if (loginButton) {
            loginButton.disabled = false;
            loginButton.innerHTML = 'Sign in';
        }
    }
});

window.addEventListener('beforeunload', function() {
    form = null;
    errorDiv = null;
    loginButton = null;
});

})();