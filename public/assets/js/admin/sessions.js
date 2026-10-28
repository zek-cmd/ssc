// Voter Sessions page: lists active voter sessions with device details and revokes one.
(function () {
    var tbody = document.getElementById('sessionsTableBody');
    var refreshBtn = document.getElementById('refreshSessionsBtn');

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
    }

    function render(sessions) {
        if (!sessions.length) {
            showMessage('No active voter sessions right now.');
            return;
        }
        tbody.innerHTML = '';
        sessions.forEach(function (s) {
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
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'px-3 py-1.5 rounded-xl text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-950/50 transition-all duration-300';
            btn.textContent = 'Revoke';
            btn.addEventListener('click', function () {
                revoke(s.username, s.ref);
            });
            actionTd.appendChild(btn);
            tr.appendChild(actionTd);
            tbody.appendChild(tr);
        });
    }

    function load() {
        showMessage('Loading...');
        apiGet(window.SSC.appUrl + '/admin/api/sessions', function (res) {
            if (!res || !res.success) {
                showMessage((res && res.error) || 'Could not load sessions.');
                return;
            }
            render(res.sessions || []);
        });
    }

    function revoke(username, ref) {
        if (!confirm('Log out ' + username + ' from this session?')) return;
        apiPost(window.SSC.appUrl + '/admin/api/sessions/revoke', { ref: ref }, function (res) {
            if (res && res.success) {
                load();
            } else {
                alert((res && res.error) || 'Could not revoke this session.');
            }
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

    if (refreshBtn) refreshBtn.addEventListener('click', load);
    load();
})();
