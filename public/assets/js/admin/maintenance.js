// Maintenance Mode
(function() {
    var toggleBtn = document.getElementById('toggleSiteBtn');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', async function() {
            var fd = new FormData();
            fd.append('csrf_token', window.SSC.csrfToken);
            
            toggleBtn.disabled = true;
            toggleBtn.textContent = 'Toggling...';
            
            try {
                var response = await fetch(window.SSC.appUrl + '/admin/maintenance/toggle', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                var data = await response.json();
                
                if (data.success) {
                    toggleBtn.textContent = data.site_enabled ? 'Site is now UP' : 'Site is now DOWN';
                    toggleBtn.classList.add('opacity-50');
                    
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                } else {
                    toggleBtn.disabled = false;
                    toggleBtn.textContent = window.SSC.siteEnabled ? 'Take Site DOWN' : 'Bring Site UP';
                    alert(data.error || 'Failed to toggle');
                }
            } catch (error) {
                toggleBtn.disabled = false;
                toggleBtn.textContent = window.SSC.siteEnabled ? 'Take Site DOWN' : 'Bring Site UP';
                alert('An error occurred. Please try again.');
            }
        });
    }
    
    window.addEventListener('beforeunload', function() {
        toggleBtn = null;
    });
})();