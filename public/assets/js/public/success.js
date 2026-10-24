// success.js
// Success page - Display voter details and countdown redirect to homepage
(function() {
    var appUrl = window.SSC.appUrl;
    var voterId = window.SSC.voterId || '';
    var redirectSeconds = window.SSC.redirectSeconds || 10;
    var seconds = redirectSeconds;
    var countdownInterval = null;
    var countdownEl = document.getElementById('countdown');
    var redirectStarted = false;
    
    var displayVoterName = document.getElementById('displayVoterName');
    var displayVoterId = document.getElementById('displayVoterId');
    
    if (displayVoterId && voterId) {
        displayVoterId.textContent = voterId;
    }
    
    if (appUrl && voterId) {
        fetch(appUrl + '/vote/voter-details?voter_id=' + encodeURIComponent(voterId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (displayVoterName && data.voter_name) {
                    displayVoterName.textContent = data.voter_name;
                }
            } else {
                if (displayVoterName) {
                    displayVoterName.textContent = 'Not available';
                }
            }
        })
        .catch(function() {
            if (displayVoterName) {
                displayVoterName.textContent = 'Not available';
            }
        });
    }
    
    function redirectHome() {
        if (redirectStarted) return;
        redirectStarted = true;
        
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        
        window.location.href = appUrl + '/';
    }
    
    if (countdownEl) {
        countdownEl.textContent = seconds;
        
        function tick() {
            seconds--;
            countdownEl.textContent = seconds;
            if (seconds <= 0) {
                redirectHome();
            }
        }
        
        countdownInterval = setInterval(tick, 1000);
    }
    
    window.addEventListener('beforeunload', function() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        countdownEl = null;
        displayVoterName = null;
        displayVoterId = null;
    });
})();