// Voter Login with Turnstile / Offline Bypass
(function() {
var form = document.getElementById('loginForm');
if (!form) return;

var isOffline = window.SSC && window.SSC.isOffline === true;

// Turnstile callback
window.turnstileVerified = function(token) {
    var input = document.getElementById('cf-turnstile-response');
    if (input) {
        input.value = token;
    }
};

// Render Turnstile manually
function renderTurnstile() {
    // Do not render anything in offline mode
    if (isOffline) return;

    var widget = document.getElementById('turnstileWidget');

    if (
        widget &&
        typeof turnstile !== 'undefined'
    ) {
        turnstile.render('#turnstileWidget', {
            sitekey: window.SSC.turnstileSiteKey,
            theme: 'auto',
            callback: window.turnstileVerified
        });
    }
}

var checkInterval = null;

// Only wait for/load Turnstile when offline bypass is disabled
if (!isOffline) {
    if (typeof turnstile !== 'undefined') {
        renderTurnstile();
    } else {
        checkInterval = setInterval(function() {
            if (typeof turnstile !== 'undefined') {
                clearInterval(checkInterval);
                checkInterval = null;
                renderTurnstile();
            }
        }, 100);

        setTimeout(function() {
            if (checkInterval) {
                clearInterval(checkInterval);
                checkInterval = null;
            }
        }, 10000);
    }
}

// Form handler
form.addEventListener('submit', async function(e) {
    e.preventDefault();

    var errorDiv = document.getElementById('loginError');
    var loginButton = document.getElementById('loginButton');
    var turnstileInput = document.getElementById('cf-turnstile-response');
    var formData = new FormData(this);

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
            window.SSC.appUrl + '/login',
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

        if (!isOffline && turnstileInput) {
            turnstileInput.value = '';
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

        if (!isOffline && turnstileInput) {
            turnstileInput.value = '';
        }

    } finally {
        if (loginButton) {
            loginButton.disabled = false;
            loginButton.innerHTML = 'Sign in';
        }
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (checkInterval) {
        clearInterval(checkInterval);
        checkInterval = null;
    }

    form = null;
});

})();