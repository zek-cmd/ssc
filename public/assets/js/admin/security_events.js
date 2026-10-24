(function() {
    var appUrl = window.SSC.appUrl;
    var pages = { events: 1 };
    var limits = { events: 12 };
    var PAGE_TARGET = 12;
    var mobileMenuBtn, mobileMenu;
    var eventSearch;

    var trustedPolicy = null;
    if (window.trustedTypes && window.trustedTypes.createPolicy) {
        trustedPolicy = window.trustedTypes.createPolicy('ssc-security-dom', {
            createHTML: function(input) { return input; }
        });
    }

    function safeHTML(str) {
        return trustedPolicy ? trustedPolicy.createHTML(str) : str;
    }

    function applyBodyMinHeight(tbodyId, limit) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        var wrapper = tbody.closest('.overflow-x-auto');
        if (!wrapper) return;
        var firstRow = tbody.querySelector('tr');
        if (!firstRow) {
            wrapper.style.minHeight = '';
            return;
        }
        var rowHeight = firstRow.getBoundingClientRect().height;
        if (!rowHeight) {
            wrapper.style.minHeight = '';
            return;
        }
        var thead = wrapper.querySelector('thead');
        var theadHeight = thead ? thead.getBoundingClientRect().height : 0;
        var target = Math.round(rowHeight * limit + theadHeight);
        wrapper.style.minHeight = target + 'px';
    }

    function clearBodyMinHeight(tbodyId) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        var wrapper = tbody.closest('.overflow-x-auto');
        if (!wrapper) return;
        wrapper.style.minHeight = '';
    }

    mobileMenuBtn = document.getElementById('mobileMenuBtn');
    mobileMenu = document.getElementById('mobileMenu');
    var menuIconOpen = document.getElementById('menuIconOpen');
    var menuIconClose = document.getElementById('menuIconClose');

    function updateMenuIcon() {
        if (mobileMenu.classList.contains('hidden')) {
            menuIconOpen.classList.remove('hidden');
            menuIconClose.classList.add('hidden');
        } else {
            menuIconOpen.classList.add('hidden');
            menuIconClose.classList.remove('hidden');
        }
    }

    function openMobileMenu() {
        mobileMenu.classList.remove('hidden');
        mobileMenu.offsetHeight;
        mobileMenu.classList.remove('max-h-0', 'opacity-0');
        mobileMenu.classList.add('max-h-[calc(100dvh-8rem)]', 'opacity-100');
        updateMenuIcon();
    }

    function closeMobileMenu() {
        mobileMenu.classList.remove('max-h-[calc(100dvh-8rem)]', 'opacity-100');
        mobileMenu.classList.add('max-h-0', 'opacity-0');
        setTimeout(function() {
            mobileMenu.classList.add('hidden');
            updateMenuIcon();
        }, 300);
    }

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function(e) { 
            e.stopPropagation();
            if (mobileMenu.classList.contains('hidden')) {
                openMobileMenu();
            } else {
                closeMobileMenu();
            }
        });
        mobileMenu.querySelectorAll('a, button').forEach(function(el) { 
            el.addEventListener('click', function() { closeMobileMenu(); }); 
        });
        document.addEventListener('click', function(e) { 
            if (!mobileMenu.classList.contains('hidden') && !mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                closeMobileMenu();
            }
        });
        document.addEventListener('touchstart', function(e) { 
            if (!mobileMenu.classList.contains('hidden') && !mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                closeMobileMenu();
            }
        }, { passive: true });
    }

    function apiGet(url, callback) { 
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(callback)
            .catch(function() {
                Swal.fire({ text: 'Network error. Please try again.', icon: 'error' });
            }); 
    }

function getEventBadge(eventType) {
    var colors = {
        'MAINTENANCE_DISABLED': 'badge-green',  
        'MAINTENANCE_ENABLED': 'badge-red',    
        'AUDIT_LOGS_CLEARED': 'badge-red',
        'ELECTION_DATA_DELETED': 'badge-red'
    };
    var labels = {
        'MAINTENANCE_DISABLED': 'Site UP',     
        'MAINTENANCE_ENABLED': 'Site DOWN',     
        'AUDIT_LOGS_CLEARED': 'Logs Cleared',
        'ELECTION_DATA_DELETED': 'Data Deleted'
    };
    
    var badgeClass = colors[eventType] || 'badge-blue';
    var label = labels[eventType] || eventType.replace(/_/g, ' ');
    
    return '<span class="' + badgeClass + '">' + label + '</span>';
}

    eventSearch = document.getElementById('eventSearch');
    if (eventSearch) {
        var searchTimer = null;
        eventSearch.addEventListener('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() { loadEvents(1); }, 500);
        });
    }

    function loadEvents(page, search) {
        page = page || pages.events;
        search = search || (eventSearch ? eventSearch.value : '');
        pages.events = page;
        
        var params = '?page=' + page + '&target=' + PAGE_TARGET;
        if (search) params += '&search=' + encodeURIComponent(search);
        
        apiGet(appUrl + '/admin/api/security-events' + params, function(data) {
            if (!data.success) {
                return;
            }
            
            if (data.limit) limits.events = data.limit;
            
            var h = '';
            if (!data.data.length) {
                h = '<tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">No security events found</td></tr>';
            } else {
                for (var i = 0; i < data.data.length; i++) {
                    var event = data.data[i];
                    var createdAt = event.created_at || '-';
                    
                    h += '<tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 row-fixed">';
                    h += '<td class="cell-desktop px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">' + createdAt + '</td>';
                    h += '<td class="cell-desktop px-4 py-3 font-semibold text-zinc-900 dark:text-white"><span class="block truncate">' + (event.admin_name || 'System') + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3">' + getEventBadge(event.event_type) + '</td>';
                    h += '<td class="cell-desktop px-4 py-3 text-zinc-600 dark:text-zinc-400 text-xs"><span class="block truncate">' + (event.description || '-') + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3 text-xs text-zinc-400 dark:text-zinc-500 whitespace-nowrap">' + (event.ip_address || '-') + '</td>';
                    
                    h += '<td class="cell-mobile px-4 py-3" colspan="5"><div class="space-y-2 mobile-card-fixed">';
                    h += '<div class="flex items-center justify-between min-w-0"><span class="font-semibold text-zinc-900 dark:text-white text-sm truncate">' + (event.admin_name || 'System') + '</span>' + getEventBadge(event.event_type) + '</div>';
                    h += '<p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">' + (event.description || '-') + '</p>';
                    h += '<div class="flex items-center justify-between text-xs text-zinc-400 dark:text-zinc-500"><span class="truncate">' + createdAt + '</span><span class="truncate">IP: ' + (event.ip_address || '-') + '</span></div>';
                    h += '</div></td></tr>';
                }
            }
            document.getElementById('eventsTableBody').innerHTML = safeHTML(h);
            document.getElementById('eventsInfo').textContent = 'Page ' + data.page + ' of ' + data.total_pages + ' (' + data.total + ' total)';

            var currentPage = data.page;
            var totalPages = data.total_pages;
            var ph = '';
            var pageNumbers = [];

            if (totalPages <= 5) {
                for (var j = 1; j <= totalPages; j++) {
                    pageNumbers.push(j);
                }
            } else {
                pageNumbers.push(1);
                if (currentPage > 3) pageNumbers.push('...');
                for (var m = Math.max(2, currentPage - 1); m <= Math.min(totalPages - 1, currentPage + 1); m++) {
                    pageNumbers.push(m);
                }
                if (currentPage < totalPages - 2) pageNumbers.push('...');
                pageNumbers.push(totalPages);
            }

            for (var k = 0; k < pageNumbers.length; k++) {
                if (pageNumbers[k] === '...') {
                    ph += '<span class="px-2 py-1 text-sm text-zinc-400 dark:text-zinc-500">...</span>';
                } else {
                    ph += '<button class="page-btn px-3 py-1 rounded-2xl text-sm font-medium ' + (pageNumbers[k] === currentPage ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700') + '" data-page="' + pageNumbers[k] + '">' + pageNumbers[k] + '</button>';
                }
            }

            document.getElementById('eventsPages').innerHTML = safeHTML(ph);

            var pageBtns = document.querySelectorAll('.page-btn');
            for (var l = 0; l < pageBtns.length; l++) {
                pageBtns[l].addEventListener('click', function() { loadEvents(parseInt(this.dataset.page)); });
            }

            if (data.pad) { applyBodyMinHeight('eventsTableBody', limits.events); } else { clearBodyMinHeight('eventsTableBody'); }
        });
    }

    window.addEventListener('beforeunload', function() {
        mobileMenuBtn = null;
        mobileMenu = null;
        eventSearch = null;
    });

    loadEvents();
})();