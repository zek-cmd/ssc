// Sidebar collapse toggle with session persistence + FOUC prevention
(function() {
    var sidebar = document.getElementById('adminSidebar');
    var collapseBtn = document.getElementById('sidebarCollapseBtn');
    var collapseBtnCollapsed = document.getElementById('sidebarCollapseBtnCollapsed');
    var logoutButton = document.getElementById('sidebarLogoutButton');

    if (!sidebar) return;

    function setCollapsed(isCollapsed, skipSave) {
        document.documentElement.classList.toggle('sidebar-is-collapsed', isCollapsed);

        if (isCollapsed) {
            sidebar.classList.add('sidebar-collapsed');
            sidebar.classList.remove('w-72');
            sidebar.classList.add('w-16');
        } else {
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.classList.remove('w-16');
            sidebar.classList.add('w-72');
        }

        var expandedItems = sidebar.querySelectorAll('.sidebar-expanded-item');
        for (var i = 0; i < expandedItems.length; i++) {
            if (isCollapsed) {
                expandedItems[i].classList.add('hidden');
            } else {
                expandedItems[i].classList.remove('hidden');
            }
        }

        var collapsedItems = sidebar.querySelectorAll('.sidebar-collapsed-item');
        for (var j = 0; j < collapsedItems.length; j++) {
            if (isCollapsed) {
                collapsedItems[j].classList.remove('hidden');
                collapsedItems[j].classList.add('flex');
            } else {
                collapsedItems[j].classList.add('hidden');
                collapsedItems[j].classList.remove('flex');
            }
        }

        var expandedHeader = sidebar.querySelector('.sidebar-expanded-header');
        var collapsedHeader = sidebar.querySelector('.sidebar-collapsed-header');
        if (expandedHeader) {
            expandedHeader.classList.toggle('hidden', isCollapsed);
            expandedHeader.classList.toggle('flex', !isCollapsed);
        }
        if (collapsedHeader) {
            collapsedHeader.classList.toggle('hidden', !isCollapsed);
            collapsedHeader.classList.toggle('flex', isCollapsed);
        }

        var logo = sidebar.querySelector('.sidebar-logo');
        if (logo) logo.classList.toggle('hidden', isCollapsed);

        var labels = sidebar.querySelectorAll('.sidebar-label');
        for (var k = 0; k < labels.length; k++) {
            labels[k].classList.toggle('hidden', isCollapsed);
        }

        if (logoutButton) {
    if (isCollapsed) {
        logoutButton.setAttribute('title', 'Logout');
    } else {
        logoutButton.removeAttribute('title');
    }
}

        var userInfo = sidebar.querySelector('.sidebar-user-info');
        if (userInfo) userInfo.classList.toggle('hidden', isCollapsed);

        var toggleSwitches = sidebar.querySelectorAll('.sidebar-toggle-switch');
        for (var l = 0; l < toggleSwitches.length; l++) {
            toggleSwitches[l].classList.toggle('hidden', isCollapsed);
        }

        if (!skipSave) {
            sessionStorage.setItem('ssc_sidebar_collapsed', isCollapsed ? '1' : '0');
        }
    }

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function() {
            sidebar.classList.remove('transition-none');
            setCollapsed(true);
        });
    }

    if (collapseBtnCollapsed) {
        collapseBtnCollapsed.addEventListener('click', function() {
            sidebar.classList.remove('transition-none');
            setCollapsed(false);
        });
    }

    // Restore state without animation
    sidebar.classList.add('transition-none');

    var savedState = sessionStorage.getItem('ssc_sidebar_collapsed');
    if (savedState === '1') {
        setCollapsed(true, true);
    } else {
        setCollapsed(false, true);
        document.documentElement.classList.remove('sidebar-is-collapsed');
    }

    setTimeout(function() {
        sidebar.classList.remove('transition-none');
    }, 50);
})();