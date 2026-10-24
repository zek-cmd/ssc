// Admin Layout - Session Ping & Loader
(function() {
    var SESSION_LIFETIME = (window.SSC.sessionLifetime || 1800) * 1000;
    var PING_INTERVAL = 120000;
    var inactivityTimer;
    var pingInterval = null;
    var loader = document.getElementById('pageLoader');
    var isTabActive = !document.hidden && document.hasFocus();
    
    function resetTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(sessionExpired, SESSION_LIFETIME);
    }
    
    function sessionExpired() {
        // POST logout with CSRF token
        var formData = new FormData();
        formData.append('csrf_token', window.SSC.csrfToken);
        
        fetch(window.SSC.appUrl + '/admin/logout', {
            method: 'POST',
            body: formData,
            headers: { 
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function() {
            window.location.href = window.SSC.appUrl + '/admin';
        }).catch(function() {
            window.location.href = window.SSC.appUrl + '/admin';
        });
    }
    
    function pingSession() {
        if (!window.SSC.isAuthenticated) return;
        if (!isTabActive) return;
        var formData = new FormData();
        formData.append('csrf_token', window.SSC.csrfToken);
        fetch(window.SSC.appUrl + '/admin/heartbeat', {
            method: 'POST',
            body: formData,
            headers: { 
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function(response) {
            if (response.status === 401) {
                sessionExpired();
                return null;
            }
            return response.json();
        }).then(function(data) {
            if (data && data.success && data.session_expires > 0) {
                resetTimer();
            } else if (data && data.session_expires <= 0) {
                sessionExpired();
            }
        }).catch(function() {});
    }
    
    document.addEventListener('visibilitychange', function() {
        isTabActive = !document.hidden;
        // No manual pingSession() call - let interval handle it
    });
    
    window.addEventListener('focus', function() {
        isTabActive = true;
        // No manual pingSession() call - let interval handle it
    });
    
    window.addEventListener('blur', function() {
        isTabActive = false;
    });
    
    ['mousemove', 'keypress', 'scroll', 'click', 'touchstart'].forEach(function(event) {
        document.addEventListener(event, function() { 
            if (window.SSC.isAuthenticated) resetTimer(); 
        }, { passive: true });
    });
    
    if (window.SSC.isAuthenticated) {
        resetTimer();
        pingInterval = setInterval(pingSession, PING_INTERVAL);
    }
    
    if (!document.documentElement.classList.contains('assets-cached')) {
        function hideLoader() {
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(function() { if (loader) loader.style.display = 'none'; }, 300);
            }
            sessionStorage.setItem('ssc_admin_assets_cached', Date.now().toString());
        }
        
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(hideLoader, 300);
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(hideLoader, 300);
            });
        }
        
        setTimeout(hideLoader, 6000);
    }
    
    window.addEventListener('beforeunload', function() {
        if (pingInterval) {
            clearInterval(pingInterval);
            pingInterval = null;
        }
        if (inactivityTimer) {
            clearTimeout(inactivityTimer);
            inactivityTimer = null;
        }
        loader = null;
    });
})();