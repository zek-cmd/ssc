(function() {
    var csrfToken = window.SSC.csrfToken;
    var appUrl = window.SSC.appUrl;
    var isSuperAdmin = window.SSC.isSuperAdmin === true;
    var canEditInfo = window.SSC.canEditInfo === true;
    var pages = { positions: 1, candidates: 1, partylists: 1, sections: 1 };
    var limits = { positions: 12, candidates: 12, partylists: 12 };
    var selected = { positions: new Set(), candidates: new Set(), partylists: new Set() };
    var sortablePositions = null, sortablePartylists = null, sortableSections = null;
    var mobileMenuBtn, mobileMenu;
    var PAGE_TARGET = 12;

    var trustedPolicy = null;
    if (window.trustedTypes && window.trustedTypes.createPolicy) {
        trustedPolicy = window.trustedTypes.createPolicy('ssc-admin-dom', {
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

    var votingControlsModal, votingControlsBackdrop, closeVotingControlsBtn;
    var votingControlsBtn, votingControlsBtnMobile, votingControlsBtnCollapsed;
    var loginToggleSwitch, voteToggleSwitch, guestToggleSwitch, testToggleSwitch;

   function initVotingControls() {
    votingControlsModal = document.getElementById('votingControlsModal');
    votingControlsBackdrop = document.getElementById('votingControlsBackdrop');
    closeVotingControlsBtn = document.getElementById('closeVotingControlsBtn');
    votingControlsBtn = document.getElementById('votingControlsBtn');
    votingControlsBtnMobile = document.getElementById('votingControlsBtnMobile');
    votingControlsBtnCollapsed = document.getElementById('votingControlsBtnCollapsed');
    loginToggleSwitch = document.getElementById('loginToggleSwitch');
    voteToggleSwitch = document.getElementById('voteToggleSwitch');
    guestToggleSwitch = document.getElementById('guestToggleSwitch');
    testToggleSwitch = document.getElementById('testToggleSwitch');

    function openModal() {
        if (votingControlsModal) {
            votingControlsModal.classList.remove('hidden');
            loadToggleStates();
        }
    }

    function closeModal() {
        if (votingControlsModal) {
            votingControlsModal.classList.add('hidden');
        }
    }

    if (votingControlsBtn) {
        votingControlsBtn.addEventListener('click', openModal);
    }
    if (votingControlsBtnCollapsed) {
        votingControlsBtnCollapsed.addEventListener('click', openModal);
    }
    if (votingControlsBtnMobile) {
        votingControlsBtnMobile.addEventListener('click', function() {
            if (mobileMenu) mobileMenu.classList.add('hidden');
            openModal();
        });
    }
    if (closeVotingControlsBtn) {
        closeVotingControlsBtn.addEventListener('click', closeModal);
    }
    if (votingControlsBackdrop) {
        votingControlsBackdrop.addEventListener('click', closeModal);
    }

    function loadToggleStates() {
        apiGet(appUrl + '/admin/api/toggle-states', function(d) {
            if (!d.success) return;
            updateSwitchUI(loginToggleSwitch, d.login_enabled);
            updateSwitchUI(voteToggleSwitch, d.vote_enabled);
            updateSwitchUI(guestToggleSwitch, d.guest_enabled);
            updateSwitchUI(testToggleSwitch, d.test_enabled);
        });
    }

    function updateSwitchUI(switchEl, enabled) {
        if (!switchEl) return;
        switchEl.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        if (enabled) {
            switchEl.classList.add('toggle-on');
        } else {
            switchEl.classList.remove('toggle-on');
        }
    }

    function handleToggle(switchEl, type) {
        if (!switchEl) return;
        switchEl.addEventListener('click', function() {
            var current = switchEl.getAttribute('aria-pressed') === 'true';
            var newState = !current;
            apiPost(appUrl + '/admin/api/toggle-state', {
                type: type,
                enabled: newState ? '1' : '0'
            }, function(d) {
                if (d.success) {
                    updateSwitchUI(switchEl, newState);
                }
            });
        });
    }

    handleToggle(loginToggleSwitch, 'login');
    handleToggle(voteToggleSwitch, 'vote');
    handleToggle(guestToggleSwitch, 'guest');
    handleToggle(testToggleSwitch, 'test');
}

    var currentSection = null;
    var dynamicAddBtn = document.getElementById('dynamicAddBtn');
    
  document.querySelectorAll('[data-action="switch-section"]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        var section = this.dataset.section;
        currentSection = section;
        
        document.querySelectorAll('.stat-card').forEach(function(card) {
            if (card.dataset.section === section) {
                card.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50/50', 'dark:bg-blue-950/30');
            } else {
                card.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50/50', 'dark:bg-blue-950/30');
            }
        });

        document.querySelectorAll('nav [data-action="switch-section"]').forEach(function(btn) {
            if (btn.dataset.section === section) {
                btn.classList.remove('text-zinc-500', 'dark:text-zinc-400');
                btn.classList.add('text-blue-600', 'dark:text-blue-400');
            } else {
                btn.classList.remove('text-blue-600', 'dark:text-blue-400');
                btn.classList.add('text-zinc-500', 'dark:text-zinc-400');
            }
        });

        var welcomeScreen = document.getElementById('welcomeScreen');
        if (welcomeScreen) welcomeScreen.classList.add('hidden');
        
        var sectionContent = document.getElementById('sectionContent');
        if (sectionContent) sectionContent.classList.remove('hidden');
        
        ['positions','candidates','partylists'].forEach(function(s) {
            var el = document.getElementById(s + 'Section');
            if (el) el.classList.add('hidden');
            selected[s].clear();
            
            var selectAllCheckbox = document.querySelector('.select-all-' + s);
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            
            updateBulkBtn(s, '.bulk-delete-' + s);
        });
        
        if (sortablePositions) { sortablePositions.destroy(); sortablePositions = null; }
        if (sortablePartylists) { sortablePartylists.destroy(); sortablePartylists = null; }
        
        var sectionsPanel = document.getElementById('sectionsSection');
        if (sectionsPanel && section !== 'sections') sectionsPanel.classList.add('hidden');
        var sectionEl = document.getElementById(section + 'Section');
        if (sectionEl) {
            sectionEl.classList.remove('hidden');
            if (section === 'positions') loadPositions();
            else if (section === 'candidates') loadCandidates();
            else if (section === 'partylists') loadPartylists();
            else if (section === 'sections') loadSections();
        }
        
        if (dynamicAddBtn && canEditInfo) {
            dynamicAddBtn.classList.remove('hidden');
            dynamicAddBtn.classList.add('flex');
        }
    });
});

    if (dynamicAddBtn) {
        dynamicAddBtn.addEventListener('click', function() {
            if (currentSection === 'positions') openPositionModal();
            else if (currentSection === 'candidates') openCandidateModal();
            else if (currentSection === 'partylists') openPartylistModal();
            else if (currentSection === 'sections') openSectionModal();
        });
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
 function renderPagination(infoId, pagesId, data, loadFn) {
    var infoEl = document.getElementById(infoId);
    var pagesEl = document.getElementById(pagesId);
    if (infoEl) infoEl.textContent = 'Page ' + data.page + ' of ' + data.total_pages + ' (' + data.total + ' total)';
    if (!pagesEl) return;
    
    var currentPage = data.page;
    var totalPages = data.total_pages;
    var h = '';
    var pageNumbers = [];
    
    if (totalPages <= 5) {
        for (var i = 1; i <= totalPages; i++) {
            pageNumbers.push(i);
        }
    } else {
        pageNumbers.push(1);
        if (currentPage > 3) pageNumbers.push('...');
        for (var j = Math.max(2, currentPage - 1); j <= Math.min(totalPages - 1, currentPage + 1); j++) {
            pageNumbers.push(j);
        }
        if (currentPage < totalPages - 2) pageNumbers.push('...');
        pageNumbers.push(totalPages);
    }
    
    for (var k = 0; k < pageNumbers.length; k++) {
        if (pageNumbers[k] === '...') {
            h += '<span class="px-2 py-1 text-sm text-zinc-400 dark:text-zinc-500">...</span>';
        } else {
            h += '<button class="page-btn px-3 py-1 rounded-2xl text-sm font-medium ' + (pageNumbers[k] === currentPage ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700') + '" data-page="' + pageNumbers[k] + '">' + pageNumbers[k] + '</button>';
        }
    }
    
    pagesEl.innerHTML = safeHTML(h);
    var btns = pagesEl.querySelectorAll('.page-btn');
    for (var l = 0; l < btns.length; l++) {
        btns[l].addEventListener('click', function() { loadFn(parseInt(this.dataset.page)); });
    }
}

    function apiGet(url, callback) { 
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(callback)
            .catch(function() {
                Swal.fire({ text: 'Network error. Please try again.', icon: 'error' });
            }); 
    }
    
    function apiPost(url, data, callback) {
        var fd = new FormData();
        for (var k in data) fd.append(k, data[k]);
        fd.append('csrf_token', csrfToken);
        fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(callback)
            .catch(function() {
                Swal.fire({ text: 'Network error. Please try again.', icon: 'error' });
            });
    }
    
    function apiDelete(url, id, callback) {
        var fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fd.append('id', id);
        fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(callback)
            .catch(function() {
                Swal.fire({ text: 'Network error. Please try again.', icon: 'error' });
            });
    }
    
    function showToast(msg, icon) { Swal.fire({ text: msg, icon: icon || 'success', timer: 1500, showConfirmButton: false }); }
    
    async function confirmDelete(title, text) {
        var result = await Swal.fire({ title: title, text: text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Delete' });
        return result.isConfirmed;
    }

    function refreshStats() {
        apiGet(appUrl + '/admin/api/stats', function(d) {
            if (!d.success) return;
            
            var desktopPositions = document.getElementById('statPositionsCount');
            var desktopCandidates = document.getElementById('statCandidatesCount');
            var desktopPartylists = document.getElementById('statPartylistsCount');
            var mobilePositions = document.getElementById('mobilePositionsCount');
            var mobileCandidates = document.getElementById('mobileCandidatesCount');
            var mobilePartylists = document.getElementById('mobilePartylistsCount');
            var mobileSections = document.getElementById('mobileSectionsCount');
            
            if (desktopPositions) desktopPositions.textContent = d.positions.toLocaleString();
            if (desktopCandidates) desktopCandidates.textContent = d.candidates.toLocaleString();
            if (desktopPartylists) desktopPartylists.textContent = d.partylists.toLocaleString();
            if (mobilePositions) mobilePositions.textContent = d.positions.toLocaleString();
            if (mobileCandidates) mobileCandidates.textContent = d.candidates.toLocaleString();
            if (mobilePartylists) mobilePartylists.textContent = d.partylists.toLocaleString();
            if (mobileSections) mobileSections.textContent = (d.sections || 0).toLocaleString();
        });
    }

    function updateBulkBtn(section, selector) {
        if (!canEditInfo) return;
        var btn = document.querySelector(selector);
        if (!btn) return;
        var sel = selected[section];
        if (sel.size > 0) { btn.classList.remove('hidden'); btn.textContent = 'Delete (' + sel.size + ')'; }
        else { btn.classList.add('hidden'); }
    }

    function setupSelectAll(selectAllSelector, tbodyId, section, btnSelector) {
        if (!canEditInfo) return;
        var selectAll = document.querySelector(selectAllSelector);
        if (!selectAll) return;
        selectAll.addEventListener('change', function() {
            var cbs = document.querySelectorAll('#' + tbodyId + ' input[type="checkbox"]');
            for (var i = 0; i < cbs.length; i++) {
                cbs[i].checked = this.checked;
                if (this.checked) selected[section].add(cbs[i].value);
                else selected[section].delete(cbs[i].value);
            }
            updateBulkBtn(section, btnSelector);
        });
    }

function setupBulkDelete(btnSelector, section, loadFn, apiUrl) {
    if (!canEditInfo) return;
    var btn = document.querySelector(btnSelector);
    if (!btn) return;
    
    var sectionLabels = {
        'positions': 'position',
        'candidates': 'candidate',
        'partylists': 'partylist'
    };
    
    var sectionLabel = sectionLabels[section] || section;
    
    btn.addEventListener('click', async function() {
        if (!selected[section].size) return;
        
        var count = selected[section].size;
        var itemText = count === 1 ? sectionLabel : sectionLabel + 's';
        
        var deleteText = 'Delete ' + count + ' ' + itemText + '?';
        
        if (!await confirmDelete('Delete?', deleteText)) return;
        
        var ids = JSON.stringify(Array.from(selected[section]));
        apiPost(apiUrl, { ids: ids }, function(d) {
            if (d.success) { 
                selected[section].clear(); 
                var sels = document.querySelectorAll('.select-all-' + section);
                for (var i = 0; i < sels.length; i++) { sels[i].checked = false; }
                loadFn(); refreshStats(); showToast(d.message); 
            } else {
                Swal.fire({ text: d.error || 'Delete failed', icon: 'error' });
            }
        });
    });
}

    if (canEditInfo) {
        var positionSearch = document.getElementById('positionSearch');
if (positionSearch) {
    var positionSearchTimer = null;
    positionSearch.addEventListener('keyup', function() {
        clearTimeout(positionSearchTimer);
        positionSearchTimer = setTimeout(function() { loadPositions(1); }, 500);
    });
}
        setupSelectAll('.select-all-positions', 'positionsTableBody', 'positions', '.bulk-delete-positions');
        setupBulkDelete('.bulk-delete-positions', 'positions', loadPositions, appUrl + '/admin/api/positions/bulk-delete');
        var addPositionBtn = document.getElementById('addPositionBtn');
        if (addPositionBtn) addPositionBtn.addEventListener('click', function() { openPositionModal(); });
        var closePositionBtn = document.getElementById('closePositionModalBtn');
        if (closePositionBtn) closePositionBtn.addEventListener('click', function() { document.getElementById('positionModal').classList.add('hidden'); });
        var positionBackdrop = document.getElementById('positionModalBackdrop');
        if (positionBackdrop) positionBackdrop.addEventListener('click', function() { document.getElementById('positionModal').classList.add('hidden'); });
        var positionForm = document.getElementById('positionForm');
        if (positionForm) positionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var data = {}; new FormData(this).forEach(function(v,k) { data[k] = v; });
            apiPost(appUrl + '/admin/api/positions', data, function(d) {
                if (d.success) { document.getElementById('positionModal').classList.add('hidden'); loadPositions(); refreshStats(); showToast(d.message); }
                else Swal.fire({ text: d.error, icon: 'error' });
            });
        });
    }

    function initSortablePositions() {
        if (!canEditInfo) return;
        var tbody = document.getElementById('positionsTableBody');
        if (!tbody) return;
        if (sortablePositions) { sortablePositions.destroy(); sortablePositions = null; }
        setTimeout(function() {
            sortablePositions = new Sortable(tbody, {
                animation: 150, ghostClass: 'sortable-ghost', delay: 400,
                onEnd: function() {
                    var updates = [];
                    var rows = tbody.querySelectorAll('tr');
                    for (var i = 0; i < rows.length; i++) {
                        var btn = rows[i].querySelector('.edit-btn');
                        if (btn) updates.push({ id: btn.dataset.id, sort_order: i + 1 });
                    }
                    for (var j = 0; j < updates.length; j++) {
                        apiPost(appUrl + '/admin/api/positions', updates[j], function(d) {
                            if (!d.success) showToast('Reorder failed', 'error');
                        });
                    }
                }
            });
        }, 300);
    }

    function openPositionModal(id, name, desc) {
        if (!canEditInfo) return;
        document.getElementById('positionModal').classList.remove('hidden');
        var scrollContainer = document.querySelector('#positionModal .overflow-y-auto');
        if (scrollContainer) scrollContainer.scrollTop = 0;
        if (id) { document.getElementById('positionModalTitle').textContent = 'Edit Position'; document.getElementById('posId').value = id; document.getElementById('posName').value = name || ''; document.getElementById('posDesc').value = desc || ''; }
        else { document.getElementById('positionModalTitle').textContent = 'Add Position'; document.getElementById('posId').value = ''; document.getElementById('posName').value = ''; document.getElementById('posDesc').value = ''; }
    }

    function loadPositions(page, search) {
        page = page || pages.positions; search = search || (document.getElementById('positionSearch') ? document.getElementById('positionSearch').value : ''); pages.positions = page;
        var params = '?page=' + page + '&target=' + PAGE_TARGET; if (search) params += '&search=' + encodeURIComponent(search);
        apiGet(appUrl + '/admin/api/positions' + params, function(data) {
            if (data.limit) limits.positions = data.limit;
            var h = '', colspan = canEditInfo ? 5 : 2;
            if (!data.data.length) h = '<tr><td colspan="' + colspan + '" class="px-4 py-8 text-center text-zinc-500">No positions</td></tr>';
            else {
                for (var i = 0; i < data.data.length; i++) {
                    var p = data.data[i];
                    var c = selected.positions.has(p.id.toString()) ? 'checked' : '';
                    h += '<tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 row-fixed">';
                    if (canEditInfo) { h += '<td class="cell-desktop px-4 py-3"><input type="checkbox" value="' + p.id + '" ' + c + ' class="pos-cb rounded"></td>'; h += '<td class="cell-desktop px-4 py-3 w-10"><span class="drag-handle cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 opacity-0 select-none w-4 h-4 inline-block"></span></td>'; }
                    h += '<td class="cell-desktop px-4 py-3 font-semibold text-zinc-900 dark:text-white"><span class="block truncate">' + p.name + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3 text-zinc-600 dark:text-zinc-400"><span class="block truncate">' + (p.description || '-') + '</span></td>';
                    if (canEditInfo) h += '<td class="cell-desktop px-4 py-3"><div class="flex items-center gap-3"><button class="edit-btn text-blue-600 hover:text-blue-500 text-sm font-semibold" data-id="' + p.id + '" data-name="' + (p.name||'').replace(/"/g,'&quot;') + '" data-desc="' + (p.description||'').replace(/"/g,'&quot;') + '" data-sort="' + p.sort_order + '">Edit</button><button class="del-btn text-red-500 hover:text-red-400 text-sm font-semibold" data-id="' + p.id + '">Delete</button></div></td>';
                    h += '<td class="cell-mobile px-4 py-3" colspan="' + colspan + '"><div class="space-y-2 mobile-card-fixed w-full min-w-0 max-w-full">';
                    h += '<div class="flex flex-col items-start justify-start text-left gap-3 pt-1 w-full min-w-0"><div class="flex items-center justify-start text-left gap-2 min-w-0 max-w-full">';
                    h += '<span class="font-semibold text-zinc-900 dark:text-white truncate max-w-full">' + p.name + '</span></div>';
                    if (canEditInfo) h += '<div class="flex items-center gap-3 shrink-0"><input type="checkbox" value="' + p.id + '" ' + c + ' class="pos-cb rounded shrink-0"><span class="drag-handle cursor-grab text-zinc-400 shrink-0 opacity-0 select-none w-4 h-4 inline-block"></span><button class="edit-btn text-blue-600 hover:text-blue-500 flex items-center gap-1 text-sm font-semibold px-1" data-id="' + p.id + '" data-name="' + (p.name||'').replace(/"/g,'&quot;') + '" data-desc="' + (p.description||'').replace(/"/g,'&quot;') + '" data-sort="' + p.sort_order + '"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button><button class="del-btn text-red-500 hover:text-red-400 flex items-center gap-1 text-sm font-semibold px-1" data-id="' + p.id + '"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div></div>';
                    h += '<p class="text-zinc-600 dark:text-zinc-400 text-sm truncate w-full">' + (p.description || '-') + '</p>';
                    h += '</div></td></tr>';
                }
            }
            document.getElementById('positionsTableBody').innerHTML = safeHTML(h);
            renderPagination('positionsInfo', 'positionsPages', data, loadPositions);
            if (data.pad) { applyBodyMinHeight('positionsTableBody', limits.positions); } else { clearBodyMinHeight('positionsTableBody'); }
            if (canEditInfo) {
                var cbs = document.querySelectorAll('.pos-cb');
                for (var j = 0; j < cbs.length; j++) { cbs[j].addEventListener('change', function() { if (this.checked) selected.positions.add(this.value); else selected.positions.delete(this.value); updateBulkBtn('positions', '.bulk-delete-positions'); }); }
                var saPos = document.querySelector('.select-all-positions');
                if (saPos) saPos.checked = false;
                var editBtns = document.querySelectorAll('#positionsTableBody .edit-btn');
                for (var k = 0; k < editBtns.length; k++) { editBtns[k].addEventListener('click', function() { openPositionModal(this.dataset.id, this.dataset.name, this.dataset.desc); }); }
                var delBtns = document.querySelectorAll('#positionsTableBody .del-btn');
                for (var l = 0; l < delBtns.length; l++) {
                    delBtns[l].addEventListener('click', async function() {
                        if (!await confirmDelete('Delete Position?', 'Delete this position?')) return;
                        var self = this;
                        apiDelete(appUrl + '/admin/api/positions/delete', this.dataset.id, function(d) {
                            if (d.success) { loadPositions(); refreshStats(); showToast(d.message); }
                            else Swal.fire({ text: d.error, icon: 'error' });
                        });
                    });
                }
                updateBulkBtn('positions', '.bulk-delete-positions');
                setTimeout(function() { initSortablePositions(); }, 100);
            }
        });
    }

    if (canEditInfo) {
        var candidateSearch = document.getElementById('candidateSearch');
if (candidateSearch) {
    var candidateSearchTimer = null;
    candidateSearch.addEventListener('keyup', function() {
        clearTimeout(candidateSearchTimer);
        candidateSearchTimer = setTimeout(function() { loadCandidates(1); }, 500);
    });
}
        setupSelectAll('.select-all-candidates', 'candidatesTableBody', 'candidates', '.bulk-delete-candidates');
        setupBulkDelete('.bulk-delete-candidates', 'candidates', loadCandidates, appUrl + '/admin/api/candidates/bulk-delete');
        var addCandidateBtn = document.getElementById('addCandidateBtn');
        if (addCandidateBtn) addCandidateBtn.addEventListener('click', function() { openCandidateModal(); });
        var closeCandidateBtn = document.getElementById('closeCandidateModalBtn');
        if (closeCandidateBtn) closeCandidateBtn.addEventListener('click', function() { document.getElementById('candidateModal').classList.add('hidden'); });
        var candidateBackdrop = document.getElementById('candidateModalBackdrop');
        if (candidateBackdrop) candidateBackdrop.addEventListener('click', function() { document.getElementById('candidateModal').classList.add('hidden'); });

        var candRemoveImageBtn = document.getElementById('candRemoveImageBtn');
        if (candRemoveImageBtn) {
            candRemoveImageBtn.addEventListener('click', function() {
                document.getElementById('candImagePreviewContainer').classList.add('hidden');
                document.getElementById('candImage').value = '';
                document.getElementById('candRemoveImage').value = '1';
            });
        }

        var candidateForm = document.getElementById('candidateForm');
        if (candidateForm) candidateForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            var removeImage = document.getElementById('candRemoveImage').value === '1';
            var imageFile = document.getElementById('candImage').files[0], imagePath = '';
            if (!removeImage && imageFile) {
                if (imageFile.size > 2 * 1024 * 1024) { Swal.fire({ text: 'Image must be less than 2MB', icon: 'error' }); return; }
                var imgFd = new FormData(); imgFd.append('csrf_token', csrfToken); imgFd.append('image', imageFile);
                try {
                    var imgRes = await fetch(appUrl + '/admin/api/candidates/upload-image', { method: 'POST', body: imgFd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    var imgData = await imgRes.json();
                    if (imgData.success) imagePath = imgData.image_path; else { Swal.fire({ text: imgData.error, icon: 'error' }); return; }
                } catch(e) { Swal.fire({ text: 'Failed to upload image', icon: 'error' }); return; }
            }
            var data = {}; new FormData(this).forEach(function(v,k) { data[k] = v; });
            if (imagePath) data['image_path'] = imagePath;
            if (removeImage) data['remove_image'] = '1';
            apiPost(appUrl + '/admin/api/candidates', data, function(d) {
                if (d.success) { document.getElementById('candidateModal').classList.add('hidden'); loadCandidates(); refreshStats(); showToast(d.message); }
                else Swal.fire({ text: d.error, icon: 'error' });
            });
        });
    }

    function openCandidateModal(id, name, posId, plId, platform, imagePath, isDefaultWinner) {
        if (!canEditInfo) return;
        document.getElementById('candidateModal').classList.remove('hidden');
        var scrollContainer = document.querySelector('#candidateModal .overflow-y-auto');
        if (scrollContainer) scrollContainer.scrollTop = 0;
        document.getElementById('candImage').value = '';
        document.getElementById('candRemoveImage').value = '0';
        var preview = document.getElementById('candImagePreview');
        var previewContainer = document.getElementById('candImagePreviewContainer');
        var removeBtn = document.getElementById('candRemoveImageBtn');
        if (preview) { preview.src = ''; }
        if (previewContainer) { previewContainer.classList.add('hidden'); }
        apiGet(appUrl + '/admin/api/candidates/options', function(d) {
            var posSel = document.getElementById('candPosition'), plSel = document.getElementById('candPartylist');
            if (posSel) { posSel.innerHTML = safeHTML('<option value="">Select position</option>'); if (d.positions) { for (var i = 0; i < d.positions.length; i++) { posSel.innerHTML += safeHTML('<option value="' + d.positions[i].id + '">' + d.positions[i].name + '</option>'); } } if (posId) posSel.value = posId; }
            if (plSel) { plSel.innerHTML = safeHTML('<option value="">None</option>'); if (d.partylists) { for (var j = 0; j < d.partylists.length; j++) { plSel.innerHTML += safeHTML('<option value="' + d.partylists[j].id + '">' + d.partylists[j].name + '</option>'); } } if (plId) plSel.value = plId; }
        });
        if (id) {
            document.getElementById('candidateModalTitle').textContent = 'Edit Candidate';
            document.getElementById('candId').value = id;
            document.getElementById('candName').value = name || '';
            document.getElementById('candPlatform').value = platform || '';
            document.getElementById('candDefaultWinner').checked = (isDefaultWinner === 1 || isDefaultWinner === '1');
            if (imagePath && preview && previewContainer) {
                preview.src = window.SSC.candidateImagesUrl + '/' + imagePath;
                previewContainer.classList.remove('hidden');
            }
        } else {
            document.getElementById('candidateModalTitle').textContent = 'Add Candidate';
            document.getElementById('candId').value = '';
            document.getElementById('candName').value = '';
            document.getElementById('candPlatform').value = '';
            document.getElementById('candDefaultWinner').checked = false;
        }
    }

    function loadCandidates(page, search) {
        page = page || pages.candidates; search = search || (document.getElementById('candidateSearch') ? document.getElementById('candidateSearch').value : ''); pages.candidates = page;
        var params = '?page=' + page + '&target=' + PAGE_TARGET; if (search) params += '&search=' + encodeURIComponent(search);
        apiGet(appUrl + '/admin/api/candidates' + params, function(data) {
            if (data.limit) limits.candidates = data.limit;
            var h = '', colspan = canEditInfo ? 6 : 4;
            if (!data.data.length) h = '<tr><td colspan="' + colspan + '" class="px-4 py-8 text-center text-zinc-500">No candidates</td></tr>';
            else {
                for (var i = 0; i < data.data.length; i++) {
                    var c = data.data[i];
                    var ch = selected.candidates.has(c.id.toString()) ? 'checked' : '';
                    h += '<tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 row-fixed">';
                    if (canEditInfo) h += '<td class="cell-desktop px-4 py-3"><input type="checkbox" value="' + c.id + '" ' + ch + ' class="cand-cb rounded"></td>';
                    h += '<td class="cell-desktop px-4 py-3"><img src="' + (c.image_path ? window.SSC.candidateImagesUrl + '/' + c.image_path : '') + '" class="candidate-thumb w-10 h-10 rounded-full object-cover ring-1 ring-zinc-200/60 dark:ring-zinc-700/60"></td>';
                    h += '<td class="cell-desktop px-4 py-3 font-semibold text-zinc-900 dark:text-white"><div class="flex items-center gap-2 min-w-0"><span class="truncate">' + c.name + '</span>' + (c.is_default_winner ? '<span class="shrink-0 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">Winner</span>' : '') + '</div></td>';
                    h += '<td class="cell-desktop px-4 py-3 text-zinc-600 dark:text-zinc-400"><span class="block truncate">' + (c.position_name || '-') + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3 text-zinc-600 dark:text-zinc-400"><span class="block truncate">' + (c.partylist_name || '-') + '</span></td>';
                    if (canEditInfo) h += '<td class="cell-desktop px-4 py-3"><div class="flex items-center gap-3"><button class="edit-btn text-blue-600 hover:text-blue-500 text-sm font-semibold" data-id="' + c.id + '" data-name="' + (c.name||'').replace(/"/g,'&quot;') + '" data-pos="' + c.position_id + '" data-pl="' + (c.partylist_id||'') + '" data-platform="' + (c.platform||'').replace(/"/g,'&quot;') + '" data-image="' + (c.image_path||'') + '" data-default-winner="' + (c.is_default_winner||0) + '">Edit</button><button class="del-btn text-red-500 hover:text-red-400 text-sm font-semibold" data-id="' + c.id + '">Delete</button></div></td>';
                    h += '<td class="cell-mobile px-4 py-3" colspan="' + colspan + '"><div class="space-y-2 mobile-card-fixed w-full min-w-0 max-w-full">';
                    h += '<div class="flex flex-col items-start justify-start text-left gap-3 pt-1 w-full min-w-0"><div class="flex items-center justify-start text-left gap-2 min-w-0 max-w-full">';
                    h += '<img src="' + (c.image_path ? window.SSC.candidateImagesUrl + '/' + c.image_path : '') + '" class="candidate-thumb w-8 h-8 rounded-full object-cover shrink-0"><span class="font-semibold text-zinc-900 dark:text-white truncate min-w-0">' + c.name + '</span>' + (c.is_default_winner ? '<span class="shrink-0 text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">Winner</span>' : '') + '</div>';
                    if (canEditInfo) h += '<div class="flex items-center gap-3 shrink-0"><input type="checkbox" value="' + c.id + '" ' + ch + ' class="cand-cb rounded shrink-0"><span class="drag-handle cursor-grab text-zinc-400 shrink-0 opacity-0 select-none w-4 h-4 inline-block"></span><button class="edit-btn text-blue-600 hover:text-blue-500 flex items-center gap-1 text-sm font-semibold px-1" data-id="' + c.id + '" data-name="' + (c.name||'').replace(/"/g,'&quot;') + '" data-pos="' + c.position_id + '" data-pl="' + (c.partylist_id||'') + '" data-platform="' + (c.platform||'').replace(/"/g,'&quot;') + '" data-image="' + (c.image_path||'') + '" data-default-winner="' + (c.is_default_winner||0) + '"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button><button class="del-btn text-red-500 hover:text-red-400 flex items-center gap-1 text-sm font-semibold px-1" data-id="' + c.id + '"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div></div>';
                     h += '<div class="flex flex-col gap-1 text-xs min-w-0"><span class="text-zinc-500 truncate">' + (c.position_name || '-') + '</span>' + (c.partylist_name ? '<span data-partylist-badge="' + c.partylist_name + '" data-pl-color="' + (c.partylist_badge_color || '#1e40af') + '" data-pl-text="' + (c.partylist_badge_text_color || '#ffffff') + '" class="text-xs truncate inline-block self-start px-1.5 py-0.5 rounded pl-badge border border-zinc-300 dark:border-zinc-600">' + c.partylist_name + '</span>' : '') + '</div>';
                    h += '</div></td></tr>';

                }
            }
            document.getElementById('candidatesTableBody').innerHTML = safeHTML(h);
            var thumbs = document.querySelectorAll('.candidate-thumb');
            for (var t = 0; t < thumbs.length; t++) { thumbs[t].addEventListener('error', function() { this.style.display = 'none'; }); }
            renderPagination('candidatesInfo', 'candidatesPages', data, loadCandidates);
            if (data.pad) { applyBodyMinHeight('candidatesTableBody', limits.candidates); } else { clearBodyMinHeight('candidatesTableBody'); }
            if (canEditInfo) {
                var cbs = document.querySelectorAll('.cand-cb');
                for (var j = 0; j < cbs.length; j++) { cbs[j].addEventListener('change', function() { if (this.checked) selected.candidates.add(this.value); else selected.candidates.delete(this.value); updateBulkBtn('candidates', '.bulk-delete-candidates'); }); }
                var saCand = document.querySelector('.select-all-candidates');
                if (saCand) saCand.checked = false;
                var editBtns = document.querySelectorAll('#candidatesTableBody .edit-btn');
                for (var k = 0; k < editBtns.length; k++) { editBtns[k].addEventListener('click', function() { openCandidateModal(this.dataset.id, this.dataset.name, this.dataset.pos, this.dataset.pl, this.dataset.platform, this.dataset.image, this.dataset.defaultWinner); }); }
                var delBtns = document.querySelectorAll('#candidatesTableBody .del-btn');
                for (var l = 0; l < delBtns.length; l++) {
                    delBtns[l].addEventListener('click', async function() {
                        if (!await confirmDelete('Delete Candidate?', 'Delete this candidate?')) return;
                        var self = this;
                        apiDelete(appUrl + '/admin/api/candidates/delete', this.dataset.id, function(d) {
                            if (d.success) { loadCandidates(); refreshStats(); showToast(d.message); }
                            else Swal.fire({ text: d.error, icon: 'error' });
                        });
                    });
                }
                updateBulkBtn('candidates', '.bulk-delete-candidates');
            }
        });
    }

    if (canEditInfo) {
        var partylistSearch = document.getElementById('partylistSearch');
if (partylistSearch) {
    var partylistSearchTimer = null;
    partylistSearch.addEventListener('keyup', function() {
        clearTimeout(partylistSearchTimer);
        partylistSearchTimer = setTimeout(function() { loadPartylists(1); }, 500);
    });
}
        setupSelectAll('.select-all-partylists', 'partylistsTableBody', 'partylists', '.bulk-delete-partylists');
        setupBulkDelete('.bulk-delete-partylists', 'partylists', loadPartylists, appUrl + '/admin/api/partylists/bulk-delete');
        var addPartylistBtn = document.getElementById('addPartylistBtn');
        if (addPartylistBtn) addPartylistBtn.addEventListener('click', function() { openPartylistModal(); });
        var closePartylistBtn = document.getElementById('closePartylistModalBtn');
        if (closePartylistBtn) closePartylistBtn.addEventListener('click', function() { document.getElementById('partylistModal').classList.add('hidden'); });
        var partylistBackdrop = document.getElementById('partylistModalBackdrop');
        if (partylistBackdrop) partylistBackdrop.addEventListener('click', function() { document.getElementById('partylistModal').classList.add('hidden'); });
        var partylistForm = document.getElementById('partylistForm');
        if (partylistForm) partylistForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var data = {}; new FormData(this).forEach(function(v,k) { data[k] = v; });
            apiPost(appUrl + '/admin/api/partylists', data, function(d) {
                if (d.success) { document.getElementById('partylistModal').classList.add('hidden'); loadPartylists(); refreshStats(); showToast(d.message); }
                else Swal.fire({ text: d.error, icon: 'error' });
            });
        });
    }

    function initSortablePartylists() {
        if (!canEditInfo) return;
        var tbody = document.getElementById('partylistsTableBody');
        if (!tbody) return;
        if (sortablePartylists) { sortablePartylists.destroy(); sortablePartylists = null; }
        setTimeout(function() {
            sortablePartylists = new Sortable(tbody, {
                animation: 150, ghostClass: 'sortable-ghost', delay: 400,
                onEnd: function() {
                    var updates = [];
                    var rows = tbody.querySelectorAll('tr');
                    for (var i = 0; i < rows.length; i++) {
                        var btn = rows[i].querySelector('.edit-btn');
                        if (btn) updates.push({ id: btn.dataset.id, sort_order: i + 1 });
                    }
                    for (var j = 0; j < updates.length; j++) {
                        apiPost(appUrl + '/admin/api/partylists', updates[j], function(d) {
                            if (!d.success) showToast('Reorder failed', 'error');
                        });
                    }
                }
            });
        }, 300);
    }

    function openPartylistModal(id, name, desc, badgeColor, badgeTextColor) {
        if (!canEditInfo) return;
        document.getElementById('partylistModal').classList.remove('hidden');
        var scrollContainer = document.querySelector('#partylistModal .overflow-y-auto');
        if (scrollContainer) scrollContainer.scrollTop = 0;
        if (id) {
            document.getElementById('partylistModalTitle').textContent = 'Edit Partylist';
            document.getElementById('plId').value = id;
            document.getElementById('plName').value = name || '';
            document.getElementById('plDesc').value = desc || '';
            var bg = badgeColor || '#1e40af';
            var fg = badgeTextColor || '#ffffff';
            document.getElementById('plBadgeColor').value = bg;
            document.getElementById('plBadgeColorText').value = bg;
            document.getElementById('plBadgeTextColor').value = fg;
            document.getElementById('plBadgeTextColorText').value = fg;
        } else {
            document.getElementById('partylistModalTitle').textContent = 'Add Partylist';
            document.getElementById('plId').value = '';
            document.getElementById('plName').value = '';
            document.getElementById('plDesc').value = '';
            document.getElementById('plBadgeColor').value = '#1e40af';
            document.getElementById('plBadgeColorText').value = '#1e40af';
            document.getElementById('plBadgeTextColor').value = '#ffffff';
            document.getElementById('plBadgeTextColorText').value = '#ffffff';
        }
    }

    function loadPartylists(page, search) {
        page = page || pages.partylists; search = search || (document.getElementById('partylistSearch') ? document.getElementById('partylistSearch').value : ''); pages.partylists = page;
        var params = '?page=' + page + '&target=' + PAGE_TARGET; if (search) params += '&search=' + encodeURIComponent(search);
        apiGet(appUrl + '/admin/api/partylists' + params, function(data) {
            if (data.limit) limits.partylists = data.limit;
            var h = '', colspan = canEditInfo ? 5 : 2;
            if (!data.data.length) h = '<tr><td colspan="' + colspan + '" class="px-4 py-8 text-center text-zinc-500">No partylists</td></tr>';
            else {
                for (var i = 0; i < data.data.length; i++) {
                    var p = data.data[i];
                    var c = selected.partylists.has(p.id.toString()) ? 'checked' : '';
                    h += '<tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 row-fixed">';
                    if (canEditInfo) { h += '<td class="cell-desktop px-4 py-3"><input type="checkbox" value="' + p.id + '" ' + c + ' class="pl-cb rounded"></td>'; h += '<td class="cell-desktop px-4 py-3 w-10"><span class="drag-handle cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 opacity-0 select-none w-4 h-4 inline-block"></span></td>'; }
                    h += '<td class="cell-desktop px-4 py-3 font-semibold text-zinc-900 dark:text-white"><span class="block truncate">' + p.name + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3 text-zinc-600 dark:text-zinc-400"><span class="block truncate">' + (p.description || '-') + '</span></td>';
                    if (canEditInfo) h += '<td class="cell-desktop px-4 py-3"><div class="flex items-center gap-3"><button class="edit-btn text-blue-600 hover:text-blue-500 text-sm font-semibold" data-id="' + p.id + '" data-name="' + (p.name||'').replace(/"/g,'&quot;') + '" data-desc="' + (p.description||'').replace(/"/g,'&quot;') + '" data-badge="' + (p.badge_color || '#1e40af') + '" data-badge-text="' + (p.badge_text_color || '#ffffff') + '">Edit</button><button class="del-btn text-red-500 hover:text-red-400 text-sm font-semibold" data-id="' + p.id + '">Delete</button></div></td>';
                    h += '<td class="cell-mobile px-4 py-3" colspan="' + colspan + '"><div class="space-y-2 mobile-card-fixed w-full min-w-0 max-w-full">';
                    h += '<div class="flex flex-col items-start justify-start text-left gap-3 pt-1 w-full min-w-0"><div class="flex items-center justify-start text-left gap-2 min-w-0 max-w-full">';
                    h += '<span class="font-semibold text-zinc-900 dark:text-white truncate max-w-full">' + p.name + '</span></div>';
                    if (canEditInfo) h += '<div class="flex items-center gap-3 shrink-0"><input type="checkbox" value="' + p.id + '" ' + c + ' class="pl-cb rounded shrink-0"><span class="drag-handle cursor-grab text-zinc-400 shrink-0 opacity-0 select-none w-4 h-4 inline-block"></span><button class="edit-btn text-blue-600 hover:text-blue-500 flex items-center gap-1 text-sm font-semibold px-1" data-id="' + p.id + '" data-name="' + (p.name||'').replace(/"/g,'&quot;') + '" data-desc="' + (p.description||'').replace(/"/g,'&quot;') + '" data-badge="' + (p.badge_color || '#1e40af') + '" data-badge-text="' + (p.badge_text_color || '#ffffff') + '"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button><button class="del-btn text-red-500 hover:text-red-400 flex items-center gap-1 text-sm font-semibold px-1" data-id="' + p.id + '"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div></div>';
                    h += '<p class="text-zinc-600 dark:text-zinc-400 text-sm truncate w-full">' + (p.description || '-') + '</p>';
                    h += '</div></td></tr>';
                }
            }
            document.getElementById('partylistsTableBody').innerHTML = safeHTML(h);
            renderPagination('partylistsInfo', 'partylistsPages', data, loadPartylists);
            if (data.pad) { applyBodyMinHeight('partylistsTableBody', limits.partylists); } else { clearBodyMinHeight('partylistsTableBody'); }
            if (canEditInfo) {
                var cbs = document.querySelectorAll('.pl-cb');
                for (var j = 0; j < cbs.length; j++) { cbs[j].addEventListener('change', function() { if (this.checked) selected.partylists.add(this.value); else selected.partylists.delete(this.value); updateBulkBtn('partylists', '.bulk-delete-partylists'); }); }
                var saPl = document.querySelector('.select-all-partylists');
                if (saPl) saPl.checked = false;
                var editBtns = document.querySelectorAll('#partylistsTableBody .edit-btn');
                for (var k = 0; k < editBtns.length; k++) { editBtns[k].addEventListener('click', function() { openPartylistModal(this.dataset.id, this.dataset.name, this.dataset.desc, this.dataset.badge, this.dataset.badgeText); }); }
                var delBtns = document.querySelectorAll('#partylistsTableBody .del-btn');
                for (var l = 0; l < delBtns.length; l++) {
                    delBtns[l].addEventListener('click', async function() {
                        if (!await confirmDelete('Delete Partylist?', 'Delete this partylist?')) return;
                        var self = this;
                        apiDelete(appUrl + '/admin/api/partylists/delete', this.dataset.id, function(d) {
                            if (d.success) { loadPartylists(); refreshStats(); showToast(d.message); }
                            else Swal.fire({ text: d.error, icon: 'error' });
                        });
                    });
                }
                updateBulkBtn('partylists', '.bulk-delete-partylists');
                setTimeout(function() { initSortablePartylists(); }, 100);
            }
        });
    }

    window.addEventListener('beforeunload', function() {
        if (sortablePositions) { sortablePositions.destroy(); sortablePositions = null; }
        if (sortablePartylists) { sortablePartylists.destroy(); sortablePartylists = null; }
        for (var key in selected) { selected[key].clear(); }
        mobileMenuBtn = null; mobileMenu = null;
        votingControlsModal = null;
        votingControlsBackdrop = null;
        closeVotingControlsBtn = null;
        votingControlsBtn = null;
        votingControlsBtnMobile = null;
        loginToggleSwitch = null;
        voteToggleSwitch = null;
        guestToggleSwitch = null;
        testToggleSwitch = null;
    });

    // ---- Sections (e.g. IT 101, IT 102) ----
    var sectionsSearchTimer = null;
    var selectedSections = new Set();

    function updateSectionBulkBtn() {
        var btn = document.getElementById('sectionBulkDelete');
        if (!btn) return;
        if (selectedSections.size > 0) { btn.classList.remove('hidden'); btn.textContent = 'Delete (' + selectedSections.size + ')'; }
        else btn.classList.add('hidden');
    }

    function updateSectionSelectAll() {
        var all = document.getElementById('sectionSelectAll');
        if (!all) return;
        var cbs = document.querySelectorAll('#sectionsTableBody .sec-check');
        all.checked = cbs.length > 0 && Array.from(cbs).every(function(cb) { return cb.checked; });
        updateSectionBulkBtn();
    }
    var sectionCanEdit = window.SSC.canEditInfo === true;

    function initSortableSections() {
        if (!sectionCanEdit) return;
        var tbody = document.getElementById('sectionsTableBody');
        if (!tbody) return;
        if (sortableSections) { sortableSections.destroy(); sortableSections = null; }
        setTimeout(function() {
            sortableSections = new Sortable(tbody, {
                animation: 150, ghostClass: 'sortable-ghost', delay: 400,
                onEnd: function() {
                    var updates = [];
                    var rows = tbody.querySelectorAll('tr');
                    var offset = (pages.sections - 1) * PAGE_TARGET;
                    for (var i = 0; i < rows.length; i++) {
                        var btn = rows[i].querySelector('.sec-edit-btn');
                        if (btn) updates.push({ id: btn.dataset.id, name: btn.dataset.name, sort_order: offset + i + 1 });
                    }
                    for (var j = 0; j < updates.length; j++) {
                        apiPost(appUrl + '/admin/api/sections', updates[j], function(d) {
                            if (!d.success) showToast('Reorder failed', 'error');
                        });
                    }
                }
            });
        }, 300);
    }

    function escSection(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function loadSections(page) {
        page = page || 1; pages.sections = page;
        var search = document.getElementById('sectionSearch');
        var q = search ? search.value.trim() : '';
        var params = '?page=' + page + '&target=' + PAGE_TARGET;
        if (q) params += '&search=' + encodeURIComponent(q);
        apiGet(appUrl + '/admin/api/sections' + params, function(d) {
            if (!d.success) return;
            var rows = d.data || [];
            renderPagination('sectionsInfo', 'sectionsPages', d, loadSections);
            var cols = sectionCanEdit ? 4 : 1;
            var h = '';
            if (!rows.length) {
                h = '<tr><td colspan="' + cols + '" class="px-4 py-8 text-center text-zinc-500">No sections yet.</td></tr>';
            }
            rows.forEach(function(s) {
                var chk = selectedSections.has(String(s.id)) ? 'checked' : '';
                var nm = escSection(s.name);
                var dataAttrs = 'data-id="' + s.id + '" data-name="' + nm + '" data-sort="' + (s.sort_order || 0) + '"';
                h += '<tr class="border-b border-zinc-200/60 dark:border-zinc-800/60">';
                if (sectionCanEdit) h += '<td class="cell-desktop px-4 py-3"><input type="checkbox" class="sec-check rounded" value="' + s.id + '" ' + chk + '></td>';
                if (sectionCanEdit) h += '<td class="cell-desktop px-4 py-3 w-10"><span class="drag-handle cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 opacity-0 select-none w-4 h-4 inline-block"></span></td>';
                h += '<td class="cell-desktop px-4 py-3 font-semibold text-zinc-900 dark:text-white">' + nm + '</td>';
                if (sectionCanEdit) {
                    h += '<td class="cell-desktop px-4 py-3"><div class="flex items-center gap-3">';
                    h += '<button type="button" class="sec-edit-btn text-blue-600 hover:text-blue-500 text-sm font-semibold" ' + dataAttrs + '>Edit</button>';
                    h += '<button type="button" class="sec-del-btn text-red-500 hover:text-red-400 text-sm font-semibold" data-id="' + s.id + '">Delete</button>';
                    h += '</div></td>';
                }
                h += '<td class="cell-mobile px-4 py-3" colspan="' + cols + '"><div class="space-y-2 mobile-card-fixed w-full min-w-0 max-w-full">';
                h += '<div class="flex flex-col items-start justify-start text-left gap-3 pt-1 text-left"><div class="flex items-center justify-start text-left gap-2 min-w-0 max-w-full">';
                h += '<span class="font-semibold text-zinc-900 dark:text-white truncate max-w-full">' + nm + '</span></div>';
                if (sectionCanEdit) {
                    h += '<div class="flex items-center gap-3 shrink-0">';
                    h += '<input type="checkbox" class="sec-check rounded shrink-0" value="' + s.id + '" ' + chk + '><span class="drag-handle cursor-grab text-zinc-400 shrink-0 opacity-0 select-none w-4 h-4 inline-block"></span>';
                    h += '<button type="button" class="sec-edit-btn text-blue-600 hover:text-blue-500 flex items-center gap-1 text-sm font-semibold px-1" ' + dataAttrs + '><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>';
                    h += '<button type="button" class="sec-del-btn text-red-500 hover:text-red-400 flex items-center gap-1 text-sm font-semibold px-1" data-id="' + s.id + '"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
                    h += '</div>';
                }
                h += '</div></div></td>';
                h += '</tr>';
            });
            var body = document.getElementById('sectionsTableBody');
            if (!body) return;
            body.innerHTML = safeHTML(h);
            initSortableSections();
            if (!sectionCanEdit) return;
            body.querySelectorAll('.sec-check').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    if (this.checked) selectedSections.add(this.value);
                    else selectedSections.delete(this.value);
                    updateSectionBulkBtn();
                });
            });
            updateSectionSelectAll();
            body.querySelectorAll('.sec-edit-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    openSectionModal(this.dataset.id, this.dataset.name);
                });
            });
            body.querySelectorAll('.sec-del-btn').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    if (!await confirmDelete('Delete Section?', 'Delete this section?')) return;
                    apiDelete(appUrl + '/admin/api/sections/delete', this.dataset.id, function(res) {
                        if (res.success) { loadSections(); showToast(res.message); }
                        else Swal.fire({ text: res.error, icon: 'error' });
                    });
                });
            });
        });
    }

    function openSectionModal(id, name) {
        var modal = document.getElementById('sectionModal');
        if (!modal) return;
        document.getElementById('sectionId').value = id || '';
        document.getElementById('sectionName').value = name || '';
        document.getElementById('sectionModalTitle').textContent = id ? 'Edit Section' : 'Add Section';
        modal.classList.remove('hidden');
        setTimeout(function() { document.getElementById('sectionName').focus(); }, 50);
    }

    function closeSectionModal() {
        var modal = document.getElementById('sectionModal');
        if (modal) modal.classList.add('hidden');
    }

    function saveSection() {
        var id = document.getElementById('sectionId').value;
        var name = document.getElementById('sectionName').value.trim();
        if (!name) { Swal.fire({ text: 'Section name is required', icon: 'error' }); return; }
        apiPost(appUrl + '/admin/api/sections', { id: id, name: name }, function(d) {
            if (d.success) { closeSectionModal(); loadSections(); showToast(d.message); }
            else Swal.fire({ text: d.error, icon: 'error' });
        });
    }

    var sectionSaveBtn = document.getElementById('sectionModalSave');
    if (sectionSaveBtn) sectionSaveBtn.addEventListener('click', saveSection);
    var sectionNameInput = document.getElementById('sectionName');
    if (sectionNameInput) sectionNameInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); saveSection(); }
    });
    var sectionCancelBtn = document.getElementById('sectionModalCancel');
    if (sectionCancelBtn) sectionCancelBtn.addEventListener('click', closeSectionModal);
    var sectionBackdrop = document.getElementById('sectionModalBackdrop');
    if (sectionBackdrop) sectionBackdrop.addEventListener('click', closeSectionModal);
    var sectionSelectAllEl = document.getElementById('sectionSelectAll');
    if (sectionSelectAllEl) sectionSelectAllEl.addEventListener('change', function() {
        var checked = this.checked;
        document.querySelectorAll('#sectionsTableBody .sec-check').forEach(function(cb) {
            cb.checked = checked;
            if (checked) selectedSections.add(cb.value); else selectedSections.delete(cb.value);
        });
        updateSectionBulkBtn();
    });
    var sectionBulkBtn = document.getElementById('sectionBulkDelete');
    if (sectionBulkBtn) sectionBulkBtn.addEventListener('click', async function() {
        var count = selectedSections.size;
        if (!count) return;
        if (!await confirmDelete('Delete?', 'Delete ' + count + ' section' + (count === 1 ? '' : 's') + '?')) return;
        apiPost(appUrl + '/admin/api/sections/bulk-delete', { ids: JSON.stringify(Array.from(selectedSections)) }, function(d) {
            if (d.success) {
                selectedSections.clear();
                loadSections(); refreshStats(); showToast(d.message);
            } else {
                Swal.fire({ text: d.error || 'Delete failed', icon: 'error' });
            }
        });
    });
    var sectionSearchEl = document.getElementById('sectionSearch');
    if (sectionSearchEl) sectionSearchEl.addEventListener('input', function() {
        clearTimeout(sectionsSearchTimer);
        sectionsSearchTimer = setTimeout(loadSections, 300);
    });

    refreshStats();
    initVotingControls();
})();