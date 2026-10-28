// Voter Sessions page: lists active voter sessions with device details and revokes one.
(function () {
    var tbody = document.getElementById('sessionsTableBody');
    var cards = document.getElementById('sessionsCards');
    var refreshBtn = document.getElementById('refreshSessionsBtn');
    var currentPage = 1;
    var currentSearch = '';

    // Own copies of the API helpers: this page does not load dashboard.js
    function apiGet(url, callback) {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(callback)
            .catch(function () {
                showMessage('Could not load sessions. Please refresh the page.');
            });
    }

    function apiPost(url, data, callback) {
        var fd = new FormData();
        for (var k in data) fd.append(k, data[k]);
        fd.append('csrf_token', window.SSC.csrfToken);
        fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(callback)
            .catch(function () {
                alert('Network error. Please try again.');
            });
    }

    function cell(text, extraClass) {
        var td = document.createElement('td');
        td.className = 'px-4 py-3 ' + (extraClass || 'text-zinc-700 dark:text-zinc-300');
        td.textContent = text;
        return td;
    }

    function showMessage(text) {
        tbody.innerHTML = '';
        var tr = document.createElement('tr');
        var td = document.createElement('td');
        td.colSpan = 7;
        td.className = 'px-4 py-8 text-center text-zinc-500';
        td.textContent = text;
        tr.appendChild(td);
        tbody.appendChild(tr);

        cards.innerHTML = '';
        var msg = document.createElement('p');
        msg.className = 'py-8 text-center text-zinc-500';
        msg.textContent = text;
        cards.appendChild(msg);
    }

    // One labelled row inside a mobile card
    function cardRow(label, value) {
        var row = document.createElement('div');
        row.className = 'flex justify-between gap-3 py-1.5';
        var l = document.createElement('span');
        l.className = 'text-zinc-500 dark:text-zinc-400';
        l.textContent = label;
        var v = document.createElement('span');
        v.className = 'text-right break-all text-zinc-700 dark:text-zinc-300';
        v.textContent = value;
        row.appendChild(l);
        row.appendChild(v);
        return row;
    }

    function makeRevokeButton(s, fullWidth) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = (fullWidth ? 'w-full ' : '') + 'px-3 py-1.5 rounded-xl text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-950/50 transition-all duration-300';
        btn.textContent = 'Revoke';
        btn.addEventListener('click', function () {
            revoke(s.username, s.ref);
        });
        return btn;
    }

    function render(sessions) {
        if (!sessions.length) {
            showMessage('No active voter sessions right now.');
            return;
        }
        tbody.innerHTML = '';
        cards.innerHTML = '';
        sessions.forEach(function (s) {
            // Desktop table row
            var tr = document.createElement('tr');
            tr.className = 'border-b border-zinc-200/60 dark:border-zinc-800/60';
            tr.appendChild(cell(s.username, 'px-4 py-3 font-semibold text-zinc-900 dark:text-white'));
            tr.appendChild(cell(s.host));
            tr.appendChild(cell(s.ip));
            tr.appendChild(cell(s.device));
            tr.appendChild(cell(s.browser));
            tr.appendChild(cell(s.last_activity));

            var actionTd = document.createElement('td');
            actionTd.className = 'px-4 py-3 text-right';
            actionTd.appendChild(makeRevokeButton(s, false));
            tr.appendChild(actionTd);
            tbody.appendChild(tr);

            // Mobile card
            var card = document.createElement('div');
            card.className = 'rounded-2xl border border-zinc-200/60 dark:border-zinc-800/60 bg-white dark:bg-zinc-900 p-4';
            var title = document.createElement('p');
            title.className = 'font-semibold text-zinc-900 dark:text-white mb-2';
            title.textContent = s.username;
            card.appendChild(title);
            card.appendChild(cardRow('Host name', s.host));
            card.appendChild(cardRow('IP address', s.ip));
            card.appendChild(cardRow('Device', s.device));
            card.appendChild(cardRow('Browser', s.browser));
            card.appendChild(cardRow('Last activity', s.last_activity));
            var cardAction = document.createElement('div');
            cardAction.className = 'mt-3';
            cardAction.appendChild(makeRevokeButton(s, true));
            card.appendChild(cardAction);
            cards.appendChild(card);
        });
    }

    function load() {
        showMessage('Loading...');
        var url = window.SSC.appUrl + '/admin/api/sessions?page=' + currentPage + '&search=' + encodeURIComponent(currentSearch);
        apiGet(url, function (res) {
            if (!res || !res.success) {
                showMessage((res && res.error) || 'Could not load sessions.');
                renderPager(1, 1, 0);
                return;
            }
            currentPage = res.page || 1;
            render(res.sessions || []);
            renderPager(res.page || 1, res.pages || 1, res.total || 0);
        });
    }

    // Numbered page buttons with ellipses and an info line, same as the dashboard sections
    function renderPager(page, pages, total) {
        var infoEl = document.getElementById('sessionsInfo');
        var pagesEl = document.getElementById('sessionsPages');
        if (infoEl) infoEl.textContent = 'Page ' + page + ' of ' + pages + ' (' + total + ' total)';
        if (!pagesEl) return;

        var pageNumbers = [];
        if (pages <= 5) {
            for (var i = 1; i <= pages; i++) pageNumbers.push(i);
        } else {
            pageNumbers.push(1);
            if (page > 3) pageNumbers.push('...');
            for (var j = Math.max(2, page - 1); j <= Math.min(pages - 1, page + 1); j++) pageNumbers.push(j);
            if (page < pages - 2) pageNumbers.push('...');
            pageNumbers.push(pages);
        }

        pagesEl.innerHTML = '';
        pageNumbers.forEach(function (n) {
            if (n === '...') {
                var dots = document.createElement('span');
                dots.className = 'px-2 py-1 text-sm text-zinc-400 dark:text-zinc-500';
                dots.textContent = '...';
                pagesEl.appendChild(dots);
                return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'page-btn px-3 py-1 rounded-2xl text-sm font-medium ' + (n === page ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700');
            btn.textContent = n;
            btn.addEventListener('click', function () {
                currentPage = n;
                load();
            });
            pagesEl.appendChild(btn);
        });
    }

    function revoke(username, ref) {
        Swal.fire({
            title: 'Log out this session?',
            text: 'Log out ' + username + ' from this session?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Yes, log out'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            apiPost(window.SSC.appUrl + '/admin/api/sessions/revoke', { ref: ref }, function (res) {
                if (res && res.success) {
                    load();
                } else {
                    Swal.fire({ text: (res && res.error) || 'Could not revoke this session.', icon: 'error' });
                }
            });
        });
    }

    // Hamburger menu (mobile): open/close, same behaviour as the dashboard
    var mobileMenuBtn = document.getElementById('mobileMenuBtn');
    var mobileMenu = document.getElementById('mobileMenu');
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
        setTimeout(function () {
            mobileMenu.classList.add('hidden');
            updateMenuIcon();
        }, 300);
    }

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (mobileMenu.classList.contains('hidden')) {
                openMobileMenu();
            } else {
                closeMobileMenu();
            }
        });
        mobileMenu.querySelectorAll('a, button').forEach(function (el) {
            el.addEventListener('click', function () { closeMobileMenu(); });
        });
        document.addEventListener('click', function (e) {
            if (!mobileMenu.classList.contains('hidden') && !mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                closeMobileMenu();
            }
        });
    }

    // Show the table on wide screens and the cards on narrow ones (breakpoint lg = 1024px)
    var tableWrap = document.getElementById('sessionsTableWrap');
    function applyLayout() {
        var wide = window.matchMedia('(min-width: 1024px)').matches;
        tableWrap.style.setProperty('display', wide ? 'block' : 'none', 'important');
        cards.style.setProperty('display', wide ? 'none' : 'block', 'important');
    }
    applyLayout();
    window.addEventListener('resize', applyLayout);

    if (refreshBtn) refreshBtn.addEventListener('click', load);

    // Search: wait a short pause after typing, then go back to page 1
    var searchBox = document.getElementById('sessionsSearch');
    var searchTimer = null;
    if (searchBox) {
        searchBox.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                currentSearch = searchBox.value.trim();
                currentPage = 1;
                load();
            }, 300);
        });
    }

    load();
})();
