// Account Management
(function() {
    var csrfToken = window.SSC.csrfToken;
    var appUrl = window.SSC.appUrl;
    var pages = { users: 1 };
    var selectedUsers = new Set();
    var mobileMenuBtn, mobileMenu;
    var userSearch, selectAll, bulkDeleteBtn, addUserBtn, closeUserModalBtn, userModalBackdrop, userForm;

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

    function apiGet(url, callback) { fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(r) { return r.json(); }).then(callback); }
    function apiPost(url, data, callback) {
        var fd = new FormData(); for (var k in data) fd.append(k, data[k]); fd.append('csrf_token', csrfToken);
        fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(r) { return r.json(); }).then(callback);
    }
    function showToast(msg, icon) { Swal.fire({ text: msg, icon: icon || 'success', timer: 1500, showConfirmButton: false }); }
    async function confirmDelete(title, text) {
        var result = await Swal.fire({ title: title, text: text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Delete' });
        return result.isConfirmed;
    }
    function updateBulkBtn() {
        var btn = document.getElementById('bulkDeleteBtn');
        if (btn) { if (selectedUsers.size > 0) { btn.classList.remove('hidden'); btn.textContent = 'Delete (' + selectedUsers.size + ')'; } else { btn.classList.add('hidden'); } }
    }

    function togglePermissionCheckboxes(role) {
        var auditContainer = document.getElementById('auditLogsContainer');
        var manageContainer = document.getElementById('manageAccountsContainer');
        var deleteElectionContainer = document.getElementById('deleteElectionContainer');
        var exportCsvContainer = document.getElementById('exportCsvContainer');
        var editInfoContainer = document.getElementById('editInfoContainer');
        var manageVoteContainer = document.getElementById('manageVoteContainer');
        var manageSessionsContainer = document.getElementById('manageSessionsContainer');
        var auditField = document.getElementById('userAuditLogs');
        var manageField = document.getElementById('userManageAccounts');
        var deleteElectionField = document.getElementById('userDeleteElection');
        var exportCsvField = document.getElementById('userExportCsv');
        var editInfoField = document.getElementById('userEditInfo');
        var manageVoteField = document.getElementById('userManageVote');
        var manageSessionsField = document.getElementById('userManageSessions');
        
        if (role === 'superadmin') {
            if (auditContainer) auditContainer.style.display = '';
            if (manageContainer) manageContainer.style.display = '';
            if (deleteElectionContainer) deleteElectionContainer.style.display = '';
            if (exportCsvContainer) exportCsvContainer.style.display = '';
            if (editInfoContainer) editInfoContainer.style.display = '';
            if (manageVoteContainer) manageVoteContainer.style.display = '';
            if (manageSessionsContainer) manageSessionsContainer.style.display = '';
            if (auditField) auditField.disabled = false;
            if (manageField) manageField.disabled = false;
            if (deleteElectionField) deleteElectionField.disabled = false;
            if (exportCsvField) exportCsvField.disabled = false;
            if (editInfoField) editInfoField.disabled = false;
            if (manageVoteField) manageVoteField.disabled = false;
            if (manageSessionsField) manageSessionsField.disabled = false;
        } else {
            if (auditContainer) auditContainer.style.display = 'none';
            if (manageContainer) manageContainer.style.display = 'none';
            if (deleteElectionContainer) deleteElectionContainer.style.display = 'none';
            if (exportCsvContainer) exportCsvContainer.style.display = 'none';
            if (editInfoContainer) editInfoContainer.style.display = 'none';
            if (manageVoteContainer) manageVoteContainer.style.display = 'none';
            if (manageSessionsContainer) manageSessionsContainer.style.display = 'none';
            if (auditField) {
                auditField.checked = false;
                auditField.disabled = true;
            }
            if (manageField) {
                manageField.checked = false;
                manageField.disabled = true;
            }
            if (deleteElectionField) {
                deleteElectionField.checked = false;
                deleteElectionField.disabled = true;
            }
            if (exportCsvField) {
                exportCsvField.checked = false;
                exportCsvField.disabled = true;
            }
            if (editInfoField) {
                editInfoField.checked = false;
                editInfoField.disabled = true;
            }
            if (manageVoteField) {
                manageVoteField.checked = false;
                manageVoteField.disabled = true;
            }
            if (manageSessionsField) {
                manageSessionsField.checked = false;
                manageSessionsField.disabled = true;
            }
        }
    }

    function syncModifyOwnVisibility() {
        var manageField = document.getElementById('userManageAccounts');
        var modifyOwnField = document.getElementById('userModifyOwnPassword');
        var modifyOwnContainer = document.getElementById('modifyOwnPasswordContainer');
        var roleField = document.getElementById('userRole');
        if (roleField && roleField.value === 'superadmin') {
            if (modifyOwnContainer) modifyOwnContainer.classList.remove('hidden');
            if (modifyOwnField) modifyOwnField.disabled = false;
            return;
        } else if (roleField && roleField.value === 'admin') {
            if (modifyOwnContainer) modifyOwnContainer.classList.add('hidden');
            if (modifyOwnField) { modifyOwnField.checked = true; modifyOwnField.disabled = false; }
            return;
        }
        var show = !!(manageField && manageField.checked);
        if (modifyOwnContainer) modifyOwnContainer.classList.toggle('hidden', !show);
        if (modifyOwnField) modifyOwnField.disabled = false;
    }

    userSearch = document.getElementById('userSearch');
    if (userSearch) {
        var userSearchTimer = null;
        userSearch.addEventListener('keyup', function() {
            clearTimeout(userSearchTimer);
            userSearchTimer = setTimeout(function() { loadUsers(1); }, 500);
        });
    }
    selectAll = document.getElementById('selectAll');
    if (selectAll) selectAll.addEventListener('change', function() {
        var cbs = document.querySelectorAll('#usersTableBody input[type="checkbox"]');
        for (var i = 0; i < cbs.length; i++) {
            cbs[i].checked = this.checked;
            if (this.checked) selectedUsers.add(cbs[i].value);
            else selectedUsers.delete(cbs[i].value);
        }
        updateBulkBtn();
    });
    bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    if (bulkDeleteBtn) bulkDeleteBtn.addEventListener('click', async function() {
        if (!selectedUsers.size) return;
        if (!await confirmDelete('Delete Users?', 'Delete ' + selectedUsers.size + ' users?')) return;
        apiPost(appUrl + '/admin/api/users/bulk-delete', { ids: JSON.stringify(Array.from(selectedUsers)) }, function(d) { if (d.success) { selectedUsers.clear(); loadUsers(); showToast(d.message); } });
    });
    addUserBtn = document.getElementById('addUserBtn');
    var manageField = document.getElementById('userManageAccounts');
    if (manageField) manageField.addEventListener('change', syncModifyOwnVisibility);
    
    if (window.SSC && window.SSC.canModifyOwnPasswordOnly) {
        if (addUserBtn) addUserBtn.classList.add('hidden');
        if (bulkDeleteBtn) { bulkDeleteBtn.classList.add('hidden'); bulkDeleteBtn.textContent = ''; }
        if (selectAll) { selectAll.parentElement.parentElement.classList.add('hidden'); }
    }
    
    if (addUserBtn) addUserBtn.addEventListener('click', function() { openUserModal(); });
    closeUserModalBtn = document.getElementById('closeUserModalBtn');
    if (closeUserModalBtn) closeUserModalBtn.addEventListener('click', function() { document.getElementById('userModal').classList.add('hidden'); });
    userModalBackdrop = document.getElementById('userModalBackdrop');
    if (userModalBackdrop) userModalBackdrop.addEventListener('click', function() { document.getElementById('userModal').classList.add('hidden'); });
    
    var roleField = document.getElementById('userRole');
    if (roleField) {
        roleField.addEventListener('change', function() {
            togglePermissionCheckboxes(this.value);
            syncModifyOwnVisibility();
        });
    }
    
    userForm = document.getElementById('userForm');
    if (userForm) userForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        var currentRole = document.getElementById('userRole').value;
        if (currentRole !== 'superadmin') {
            var auditField = document.getElementById('userAuditLogs');
            var manageField = document.getElementById('userManageAccounts');
            if (auditField) auditField.checked = false;
            if (manageField) manageField.checked = false;
        }
        
        var usernameField = document.getElementById('userUsername');
        var passwordField = document.getElementById('userPassword');
        var confirmField = document.getElementById('userConfirmPassword');
        if (passwordField && confirmField) {
            if (passwordField.value !== confirmField.value) {
                Swal.fire({ text: 'Passwords do not match', icon: 'error' });
                confirmField.focus();
                return;
            }
        }
        var wasDisabled = usernameField.disabled;
        if (wasDisabled) usernameField.disabled = false;
        var data = {}; new FormData(this).forEach(function(v,k) { data[k] = v; });
        // Disabled checkboxes are not sent with the form, so send the sessions flag explicitly
        var sessionsBox = document.getElementById('userManageSessions');
        if (sessionsBox && !sessionsBox.disabled && sessionsBox.checked) data['can_manage_sessions'] = '1';
        else delete data['can_manage_sessions'];
        if (wasDisabled) usernameField.disabled = true;
        apiPost(appUrl + '/admin/api/users', data, function(d) { if (d.success) { document.getElementById('userModal').classList.add('hidden'); loadUsers(); showToast(d.message); } else Swal.fire({ text: d.error, icon: 'error' }); });
    });

    // Debounced password-match validation (inline, no Swal popup)
    (function setupPasswordMatchValidation() {
        var pw = document.getElementById('userPassword');
        var cf = document.getElementById('userConfirmPassword');
        var hint = document.getElementById('confirmPasswordHint');
        if (!pw || !cf || !hint) return;
        var timer = null;
        function validate() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                var p = pw.value, c = cf.value;
                if (!p && !c) {
                    hint.textContent = '';
                    hint.classList.remove('text-red-500', 'text-green-600');
                    cf.classList.remove('border-red-500', 'border-green-500');
                    return;
                }
                if (p && !c) {
                    hint.textContent = 'Please confirm your password';
                    hint.classList.add('text-red-500');
                    hint.classList.remove('text-green-600');
                    cf.classList.add('border-red-500');
                    cf.classList.remove('border-green-500');
                    return;
                }
                if (p === c) {
                    hint.textContent = 'Passwords match';
                    hint.classList.add('text-green-600');
                    hint.classList.remove('text-red-500');
                    cf.classList.add('border-green-500');
                    cf.classList.remove('border-red-500');
                } else {
                    hint.textContent = 'Passwords do not match';
                    hint.classList.add('text-red-500');
                    hint.classList.remove('text-green-600');
                    cf.classList.add('border-red-500');
                    cf.classList.remove('border-green-500');
                }
            }, 250);
        }
        pw.addEventListener('input', validate);
        cf.addEventListener('input', validate);
    })();

    function openUserModal(id, username, name, role, canManageAudit, canManageAccounts, canDeleteElection, canExportCsv, canEditInfo, canManageVote, enabled, canModifyOwnPassword, canManageSessions) {
        document.getElementById('userModal').classList.remove('hidden');
        var scrollContainer = document.querySelector('#userModal .overflow-y-auto');
        if (scrollContainer) scrollContainer.scrollTop = 0;
        var passwordField = document.getElementById('userPassword'), usernameField = document.getElementById('userUsername');
        var roleField = document.getElementById('userRole'), auditField = document.getElementById('userAuditLogs'), manageField = document.getElementById('userManageAccounts'), deleteElectionField = document.getElementById('userDeleteElection'), exportCsvField = document.getElementById('userExportCsv'), editInfoField = document.getElementById('userEditInfo'), manageVoteField = document.getElementById('userManageVote'), passwordHint = document.getElementById('passwordHint');
        var enabledField = document.getElementById('userEnabled');
        var manageSessionsField = document.getElementById('userManageSessions');
        var modifyOwnField = document.getElementById('userModifyOwnPassword');
        var modifyOwnContainer = document.getElementById('modifyOwnPasswordContainer');
        if (window.SSC && window.SSC.canModifyOwnPasswordOnly) {
            passwordField.required = true; passwordField.placeholder = 'Enter new password'; passwordHint.textContent = 'Enter new password';
        }
        if (id) {
            document.getElementById('userModalTitle').textContent = 'Edit User'; document.getElementById('userId').value = id;
            usernameField.value = username || ''; usernameField.disabled = true;
            document.getElementById('userName').value = name || '';
            passwordField.required = false; passwordField.placeholder = 'Leave blank to keep current'; passwordHint.textContent = 'Leave blank to keep current password';
            roleField.value = role || 'admin'; auditField.checked = canManageAudit == 1; manageField.checked = canManageAccounts == 1; deleteElectionField.checked = canDeleteElection == 1; exportCsvField.checked = canExportCsv == 1; editInfoField.checked = canEditInfo == 1; manageVoteField.checked = canManageVote == 1; manageSessionsField.checked = canManageSessions == 1; enabledField.checked = enabled == 1; modifyOwnField.checked = canModifyOwnPassword == 1; syncModifyOwnVisibility();
            if (username === window.SSC.adminUsername) { roleField.disabled = true; auditField.disabled = true; manageField.disabled = true; deleteElectionField.disabled = true; exportCsvField.disabled = true; enabledField.disabled = true; modifyOwnField.disabled = true; modifyOwnField.checked = false; } else { roleField.disabled = false; auditField.disabled = false; manageField.disabled = false; deleteElectionField.disabled = false; exportCsvField.disabled = false; enabledField.disabled = false; }
            togglePermissionCheckboxes(roleField.value);
        } else {
            document.getElementById('userModalTitle').textContent = 'Add User'; document.getElementById('userId').value = '';
            usernameField.value = ''; usernameField.disabled = false; document.getElementById('userName').value = '';
            passwordField.required = true; passwordField.placeholder = 'Enter password'; passwordHint.textContent = 'Minimum 6 characters recommended';
            roleField.value = 'admin'; roleField.disabled = false; auditField.checked = false; auditField.disabled = false; manageField.checked = false; manageField.disabled = false; deleteElectionField.checked = false; deleteElectionField.disabled = false; exportCsvField.checked = false; exportCsvField.disabled = false; editInfoField.checked = false; editInfoField.disabled = false; manageVoteField.checked = false; manageVoteField.disabled = false; manageSessionsField.checked = false; manageSessionsField.disabled = false; enabledField.checked = true; enabledField.disabled = false; modifyOwnField.checked = true; syncModifyOwnVisibility();
            togglePermissionCheckboxes(roleField.value);
        }
        if (window.SSC && window.SSC.canModifyOwnPasswordOnly) {
            document.getElementById('userUsername').required = false;
            document.getElementById('userName').required = false;
            document.getElementById('userRole').required = false;
            document.getElementById('userUsername').disabled = true;
            document.getElementById('userName').disabled = true;
            document.getElementById('userRole').disabled = true;
            document.getElementById('userUsername').parentElement.classList.add('hidden');
            document.getElementById('userName').parentElement.classList.add('hidden');
            document.getElementById('userRole').parentElement.classList.add('hidden');
            document.getElementById('auditLogsContainer').classList.add('hidden');
            document.getElementById('manageAccountsContainer').classList.add('hidden');
            document.getElementById('deleteElectionContainer').classList.add('hidden');
            document.getElementById('exportCsvContainer').classList.add('hidden');
            document.getElementById('editInfoContainer').classList.add('hidden');
            document.getElementById('manageVoteContainer').classList.add('hidden');
            document.getElementById('enabledHintContainer').classList.add('hidden');
            document.getElementById('modifyOwnPasswordContainer').classList.add('hidden');
            document.getElementById('userModalTitle').textContent = 'Update Password';
            passwordField.required = true; passwordField.placeholder = 'Enter new password'; passwordHint.textContent = 'Enter new password';
        } else {
            document.getElementById('userUsername').parentElement.classList.remove('hidden');
            document.getElementById('userName').parentElement.classList.remove('hidden');
            document.getElementById('userRole').parentElement.classList.remove('hidden');
            document.getElementById('auditLogsContainer').classList.remove('hidden');
            document.getElementById('manageAccountsContainer').classList.remove('hidden');
            document.getElementById('deleteElectionContainer').classList.remove('hidden');
            document.getElementById('exportCsvContainer').classList.remove('hidden');
            document.getElementById('editInfoContainer').classList.remove('hidden');
            document.getElementById('manageVoteContainer').classList.remove('hidden');
            document.getElementById('enabledHintContainer').classList.remove('hidden');
            document.getElementById('userUsername').required = true;
            document.getElementById('userName').required = true;
            document.getElementById('userRole').required = true;
        }
        syncModifyOwnVisibility();
        passwordField.value = '';
    }

    function loadUsers(page, search) {
        page = page || pages.users; search = search || (userSearch ? userSearch.value : ''); pages.users = page;
        var params = '?page=' + page + '&limit=15'; if (search) params += '&search=' + encodeURIComponent(search);
        apiGet(appUrl + '/admin/api/users' + params, function(data) {
            var h = '';
            if (!data.data.length) h = '<tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">No users found</td></tr>';
            else {
                for (var i = 0; i < data.data.length; i++) {
                    var u = data.data[i];
                    var c = selectedUsers.has(u.id.toString()) ? 'checked' : '', isSelf = (u.username === window.SSC.adminUsername);
                    h += '<tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">';
                    h += '<td class="cell-desktop px-4 py-3"><input type="checkbox" value="' + u.id + '" ' + c + ' class="user-cb rounded"></td>';
                    h += '<td class="cell-desktop px-4 py-3 font-semibold text-zinc-900 dark:text-white">' + u.username + '</td>';
                    h += '<td class="cell-desktop px-4 py-3 text-zinc-600 dark:text-zinc-400">' + u.name + '</td>';
                    h += '<td class="cell-desktop px-4 py-3"><span class="badge-blue">' + u.role + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3">' + (u.enabled == 1 ? '<span class="badge-green">Yes</span>' : '<span class="badge-red">No</span>') + '</td>';
                    h += '<td class="cell-desktop px-4 py-3 text-xs text-zinc-400 dark:text-zinc-500">' + (u.created_at ? u.created_at.replace(' ', '<br>') : '-') + '</td>';
                    h += '<td class="cell-desktop px-4 py-3"><div class="flex items-center gap-3">' + (window.SSC && window.SSC.canModifyOwnPasswordOnly ? '<button class="update-password-btn text-blue-600 hover:text-blue-500 text-sm font-semibold" data-id="' + u.id + '" data-username="' + u.username + '">Update Password</button>' : '<button class="edit-btn text-blue-600 hover:text-blue-500 text-sm font-semibold" data-id="' + u.id + '" data-username="' + u.username + '" data-name="' + (u.name||'').replace(/"/g,'&quot;') + '" data-role="' + u.role + '" data-audit="' + u.can_manage_audit_logs + '" data-manage="' + u.can_manage_accounts + '" data-delete-election="' + (u.can_delete_election || 0) + '" data-export-csv="' + (u.can_export_csv || 0) + '" data-edit-info="' + (u.can_edit_info || 0) + '" data-manage-vote="' + (u.can_manage_vote || 0) + '" data-manage-sessions="' + (u.can_manage_sessions || 0) + '" data-enabled="' + u.enabled + '" data-modify-own="' + (u.can_modify_own_password_only || 0) + '">Edit</button>' + (isSelf ? '' : '<button class="del-btn text-red-500 hover:text-red-400 text-sm font-semibold" data-id="' + u.id + '" data-name="' + (u.name||'').replace(/"/g,'&quot;') + '">Delete</button>')) + '</div></td>';
                    h += '<td class="cell-mobile px-4 py-3" colspan="7"><div class="space-y-2">';
                    h += '<div class="flex items-center justify-between"><div class="flex items-center gap-2"><input type="checkbox" value="' + u.id + '" ' + c + ' class="user-cb rounded"><span class="font-semibold text-zinc-900 dark:text-white">' + u.username + '</span></div><span class="badge-blue text-xs">' + u.role + '</span></div>';
                    h += '<p class="text-zinc-600 dark:text-zinc-400">' + u.name + '</p>';
                    h += '<div class="flex items-center gap-2 text-xs"><span class="text-zinc-500">Enabled: ' + (u.enabled == 1 ? '<span class="badge-green text-xs">Yes</span>' : '<span class="badge-red text-xs">No</span>') + '</span></div>';
                    h += '<div class="flex items-center gap-2 text-xs"><span class="text-zinc-400 dark:text-zinc-500">Created: ' + (u.created_at ? u.created_at.replace(' ', ' ') : '-') + '</span></div>';
                    h += '<div class="flex items-center gap-3 pt-1">' + (window.SSC && window.SSC.canModifyOwnPasswordOnly ? '<button class="update-password-btn text-blue-600 hover:text-blue-500 text-sm font-semibold" data-id="' + u.id + '" data-username="' + u.username + '">Update Password</button>' : '<button class="edit-btn text-blue-600 hover:text-blue-500 text-sm font-semibold" data-id="' + u.id + '" data-username="' + u.username + '" data-name="' + (u.name||'').replace(/"/g,'&quot;') + '" data-role="' + u.role + '" data-audit="' + u.can_manage_audit_logs + '" data-manage="' + u.can_manage_accounts + '" data-delete-election="' + (u.can_delete_election || 0) + '" data-export-csv="' + (u.can_export_csv || 0) + '" data-edit-info="' + (u.can_edit_info || 0) + '" data-manage-vote="' + (u.can_manage_vote || 0) + '" data-manage-sessions="' + (u.can_manage_sessions || 0) + '" data-enabled="' + u.enabled + '" data-modify-own="' + (u.can_modify_own_password_only || 0) + '">Edit</button>' + (isSelf ? '' : '<button class="del-btn text-red-500 hover:text-red-400 text-sm font-semibold" data-id="' + u.id + '" data-name="' + (u.name||'').replace(/"/g,'&quot;') + '">Delete</button>')) + '</div></td></tr>';
                }
            }
            document.getElementById('usersTableBody').innerHTML = h;
            document.getElementById('usersInfo').textContent = 'Page ' + data.page + ' of ' + data.total_pages + ' (' + data.total + ' total)';

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

            document.getElementById('usersPages').innerHTML = ph;

            var pageBtns = document.querySelectorAll('.page-btn');
            for (var l = 0; l < pageBtns.length; l++) {
                pageBtns[l].addEventListener('click', function() { loadUsers(parseInt(this.dataset.page)); });
            }
            var cbs = document.querySelectorAll('.user-cb');
            for (var l = 0; l < cbs.length; l++) { if (window.SSC && window.SSC.canModifyOwnPasswordOnly) { cbs[l].parentElement.classList.add('hidden'); continue; } cbs[l].addEventListener('change', function() { if (this.checked) selectedUsers.add(this.value); else selectedUsers.delete(this.value); updateBulkBtn(); }); }
            if (window.SSC && window.SSC.canModifyOwnPasswordOnly) { document.getElementById('selectAll').parentElement.parentElement.classList.add('hidden'); }
            var editBtns = document.querySelectorAll('#usersTableBody .edit-btn');
            for (var m = 0; m < editBtns.length; m++) { editBtns[m].addEventListener('click', function() { openUserModal(this.dataset.id, this.dataset.username, this.dataset.name, this.dataset.role, this.dataset.audit, this.dataset.manage, this.dataset.deleteElection || '0', this.dataset.exportCsv || '0', this.dataset.editInfo || '0', this.dataset.manageVote || '0', this.dataset.enabled, this.dataset.modifyOwn || '0', this.dataset.manageSessions || '0'); }); }
            var pwBtns = document.querySelectorAll('#usersTableBody .update-password-btn');
            for (var p = 0; p < pwBtns.length; p++) { pwBtns[p].addEventListener('click', function() { openUserModal(this.dataset.id); }); }
            var delBtns = document.querySelectorAll('#usersTableBody .del-btn');
            for (var n = 0; n < delBtns.length; n++) {
                delBtns[n].addEventListener('click', async function() {
                    if (!await confirmDelete('Delete User?', 'Delete ' + this.dataset.name + '?')) return;
                    var self = this;
                    apiPost(appUrl + '/admin/api/users/delete', { id: this.dataset.id }, function(d) { if (d.success) { selectedUsers.delete(self.dataset.id); loadUsers(); showToast(d.message); } else Swal.fire({ text: d.error, icon: 'error' }); });
                });
            }
            updateBulkBtn();
        });
    }

    window.addEventListener('beforeunload', function() {
        selectedUsers.clear();
        mobileMenuBtn = null; mobileMenu = null;
        userSearch = null; selectAll = null; bulkDeleteBtn = null; addUserBtn = null;
        closeUserModalBtn = null; userModalBackdrop = null; userForm = null;
    });

    loadUsers();
})();