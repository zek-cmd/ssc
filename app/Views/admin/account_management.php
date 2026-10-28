<?php
$pageTitle = 'Accounts';
$sidebarNavItems = [
    ['url' => APP_URL . '/admin/dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
];
$mobileNavItems = [];
$extraSidebarContent = '';
$extraMobileContent = '';
?>

<div class="flex h-dvh overflow-hidden bg-zinc-50 dark:bg-zinc-950">
    <?php include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include APP_PATH . '/Views/admin/partials/header.php'; ?>

        <main class="flex-1 overflow-y-auto px-4 py-6 lg:px-8 lg:py-8 pb-20 lg:pb-8">
            <div class="max-w-full mx-auto">
                <div class="card mb-6">
                    <div class="flex flex-col sm:flex-row gap-3 mb-4">
                        <div class="flex-1 w-full"><input type="search" id="userSearch" placeholder="Search users..." autocomplete="off" class="input-field w-full"></div>
                        <button id="bulkDeleteBtn" class="btn-secondary text-sm hidden w-full sm:w-auto">Delete Selected</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="thead-desktop">
                                <tr class="border-b border-zinc-200/60 dark:border-zinc-800/60">
                                    <th class="px-4 py-3 text-left"><input type="checkbox" id="selectAll" class="rounded"></th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Username</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Role</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Enabled</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Created</th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-500 dark:text-zinc-400">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col items-start mt-4 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 gap-2">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400" id="usersInfo"></p>
                        <div class="flex gap-2 flex-wrap" id="usersPages"></div>
                    </div>
                </div>

                <button id="addUserBtn" class="fixed bottom-24 right-4 lg:bottom-8 lg:right-8 z-20 w-14 h-14 rounded-full bg-blue-600 text-white shadow-lg shadow-blue-600/30 hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-600/40 active:scale-95 transition-all duration-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                </button>

                <div id="userModal" class="fixed inset-0 z-50 hidden">
                    <div id="userModalBackdrop" class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md max-h-[90dvh] bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl shadow-zinc-500/20 ring-1 ring-zinc-200 dark:ring-zinc-800 flex flex-col sm:mx-0">
                        <div class="shrink-0 px-6 pt-6 pb-4">
                            <h3 id="userModalTitle" class="text-lg font-bold text-zinc-900 dark:text-white">Add User</h3>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6">
                        <form id="userForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="id" id="userId" value="">
    <div class="mb-4"><label for="userUsername" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Username</label><input type="text" id="userUsername" name="username" autocomplete="off" required class="input-field"></div>
    <div class="mb-4"><label for="userPassword" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Password</label><input type="password" id="userPassword" name="password" autocomplete="new-password" class="input-field" placeholder="Leave blank to keep current"><p class="text-xs text-zinc-500 mt-1" id="passwordHint"></p></div>
    <div class="mb-4"><label for="userConfirmPassword" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Confirm Password</label><input type="password" id="userConfirmPassword" name="confirm_password" autocomplete="new-password" class="input-field" placeholder="Re-enter new password"><p class="text-xs mt-1" id="confirmPasswordHint"></p></div>
    <div class="mb-4"><label for="userName" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Name</label><input type="text" id="userName" name="name" autocomplete="off" required class="input-field"></div>
    <div class="mb-4"><label for="userRole" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Role</label><select id="userRole" name="role" class="input-field"><option value="voter">Voter</option><option value="admin">Admin</option><option value="superadmin">Superadmin</option></select></div>
    <div class="mb-4" id="auditLogsContainer"><label for="userAuditLogs" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userAuditLogs" name="can_manage_audit_logs" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Can Manage Audit Logs</span></label></div>
    <div class="mb-4" id="manageAccountsContainer"><label for="userManageAccounts" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userManageAccounts" name="can_manage_accounts" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Can Manage Accounts</span></label></div>
    <div class="mb-4" id="deleteElectionContainer"><label for="userDeleteElection" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userDeleteElection" name="can_delete_election" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Can Delete Election Data</span></label></div>
    <div class="mb-4" id="exportCsvContainer"><label for="userExportCsv" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userExportCsv" name="can_export_csv" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Can Export CSV</span></label></div>
    <div class="mb-4" id="editInfoContainer"><label for="userEditInfo" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userEditInfo" name="can_edit_info" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Can Edit Info (Candidates / Positions / Partylist)</span></label></div>
    <div class="mb-4" id="manageVoteContainer"><label for="userManageVote" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userManageVote" name="can_manage_vote" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Can Manage Vote (Voting Controls)</span></label></div>
    <div class="mb-4" id="manageSessionsContainer"><label for="userManageSessions" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userManageSessions" name="can_manage_sessions" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Can Manage Sessions (View and Revoke Sessions)</span></label></div>
    <div class="mb-4" id="enabledHintContainer"><label for="userEnabled" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userEnabled" name="enabled" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Enabled</span></label><p class="text-xs text-zinc-500 mt-1">If unchecked, user cannot login</p></div>
    <div class="mb-4" id="modifyOwnPasswordContainer"><label for="userModifyOwnPassword" class="flex items-center gap-2 cursor-pointer"><input type="checkbox" id="userModifyOwnPassword" name="can_modify_own_password_only" value="1" class="rounded"><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Can modify password</span></label></div>
</form>
                        </div>
                        <div class="shrink-0 px-6 pb-6 pt-2 flex flex-col sm:flex-row justify-end gap-3">
                            <button type="button" id="closeUserModalBtn" class="btn-secondary w-full sm:w-auto">Cancel</button>
                            <button type="submit" form="userForm" class="btn-primary w-full sm:w-auto">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script nonce="<?= htmlspecialchars($nonce) ?>">
window.SSC.appUrl = '<?= APP_URL ?>';
window.SSC.csrfToken = '<?= \App\Services\SessionService::getCSRFToken() ?>';
window.SSC.canModifyOwnPasswordOnly = <?= (isset($flags['can_modify_own_password_only']) && $flags['can_modify_own_password_only'] == 1) ? 'true' : 'false' ?>;
window.SSC.role = '<?= $flags['role'] ?? '' ?>';
</script>
<script src="<?= \App\Services\AdminSecurityService::asset('js/admin/account_management.js') ?>" nonce="<?= htmlspecialchars($nonce) ?>"></script>