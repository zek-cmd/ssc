(function() {
    var csrfToken = window.SSC.csrfToken;
    var appUrl = window.SSC.appUrl;
    var pages = { logs: 1 };
    var limits = { logs: 12 };
    var PAGE_TARGET = 12;
    var mobileMenuBtn, mobileMenu;
    var logSearch, clearLogsBtn, clearLogsBtnMobile;
    var exportPollInterval = null;
    var exportProgressModal, exportProgressBar, exportProgressText, exportProgressPercent, closeExportProgressBtn;
    var auditDetailModal, auditDetailBackdrop, auditDetailContent, closeAuditDetailBtn, closeAuditDetailFooterBtn;

    var trustedPolicy = null;
    if (window.trustedTypes && window.trustedTypes.createPolicy) {
        trustedPolicy = window.trustedTypes.createPolicy('ssc-audit-dom', {
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

    exportProgressModal = document.getElementById('exportProgressModal');
    exportProgressBar = document.getElementById('exportProgressBar');
    exportProgressText = document.getElementById('exportProgressText');
    exportProgressPercent = document.getElementById('exportProgressPercent');
    closeExportProgressBtn = document.getElementById('closeExportProgressBtn');

    auditDetailModal = document.getElementById('auditDetailModal');
    auditDetailBackdrop = document.getElementById('auditDetailBackdrop');
    auditDetailContent = document.getElementById('auditDetailContent');
    closeAuditDetailBtn = document.getElementById('closeAuditDetailBtn');
    closeAuditDetailFooterBtn = document.getElementById('closeAuditDetailFooterBtn');

    function showExportModal() {
        if (exportProgressModal) {
            exportProgressModal.classList.remove('hidden');
            exportProgressBar.className = 'h-full bg-blue-600 rounded-full transition-all duration-300 w-0';
            if (exportProgressText) exportProgressText.textContent = 'Preparing export...';
            if (exportProgressPercent) exportProgressPercent.textContent = '0%';
            if (closeExportProgressBtn) closeExportProgressBtn.classList.add('hidden');
        }
    }

    function hideExportModal() {
        if (exportProgressModal) {
            exportProgressModal.classList.add('hidden');
        }
        if (exportPollInterval) {
            clearInterval(exportPollInterval);
            exportPollInterval = null;
        }
    }

    function updateExportProgress(progress, processed, total, status) {
        if (exportProgressBar) {
            exportProgressBar.className = 'h-full bg-blue-600 rounded-full transition-all duration-300';
            if (progress >= 100) {
                exportProgressBar.className += ' w-full';
            } else if (progress > 0) {
                if (progress <= 10) exportProgressBar.className += ' w-[10%]';
                else if (progress <= 20) exportProgressBar.className += ' w-[20%]';
                else if (progress <= 30) exportProgressBar.className += ' w-[30%]';
                else if (progress <= 40) exportProgressBar.className += ' w-[40%]';
                else if (progress <= 50) exportProgressBar.className += ' w-[50%]';
                else if (progress <= 60) exportProgressBar.className += ' w-[60%]';
                else if (progress <= 70) exportProgressBar.className += ' w-[70%]';
                else if (progress <= 80) exportProgressBar.className += ' w-[80%]';
                else if (progress <= 90) exportProgressBar.className += ' w-[90%]';
                else exportProgressBar.className += ' w-full';
            }
        }
        if (exportProgressText && total > 0) {
            if (status === 'complete') {
                exportProgressText.textContent = 'Export complete!';
            } else {
                exportProgressText.textContent = 'Processing row ' + processed + ' of ' + total + '...';
            }
        }
        if (exportProgressPercent) {
            exportProgressPercent.textContent = progress + '%';
        }
        if (status === 'complete' && closeExportProgressBtn) {
            closeExportProgressBtn.classList.remove('hidden');
        }
    }

    function triggerDownload(url) {
        var a = document.createElement('a');
        a.href = url;
        a.download = '';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

   function startExport() {
    showExportModal();

    fetch(appUrl + '/admin/api/audit-logs/export', {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            hideExportModal();
            Swal.fire({ text: data.error || 'Export failed', icon: 'error' });
            return;
        }

        var exportId = data.export_id;

        exportPollInterval = setInterval(function() {
            fetch(appUrl + '/admin/api/audit-logs/export-progress?export_id=' + encodeURIComponent(exportId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(progressData) {
                if (!progressData.success) {
                    clearInterval(exportPollInterval);
                    exportPollInterval = null;
                    hideExportModal();
                    Swal.fire({ text: progressData.error || 'Export failed', icon: 'error' });
                    return;
                }

                updateExportProgress(progressData.progress, progressData.processed, progressData.total, progressData.status);

                if (progressData.status === 'complete' && progressData.download_url) {
                    clearInterval(exportPollInterval);
                    exportPollInterval = null;
                    updateExportProgress(100, progressData.processed, progressData.total, 'complete');
                    triggerDownload(progressData.download_url);
                    setTimeout(function() {
                        hideExportModal();
                    }, 1000);
                }
            })
            .catch(function() {
                clearInterval(exportPollInterval);
                exportPollInterval = null;
                hideExportModal();
                Swal.fire({ text: 'Failed to check export progress', icon: 'error' });
            });
        }, 500);
    })
    .catch(function() {
        hideExportModal();
        Swal.fire({ text: 'Failed to start export', icon: 'error' });
    });
}

    function apiGet(url, callback) { fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(r) { return r.json(); }).then(callback); }
    function apiPost(url, data, callback) {
        var fd = new FormData(); for (var k in data) fd.append(k, data[k]); fd.append('csrf_token', csrfToken);
        fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(r) { return r.json(); }).then(callback);
    }
    function showToast(msg, icon) { Swal.fire({ text: msg, icon: icon || 'success', timer: 1500, showConfirmButton: false }); }

    logSearch = document.getElementById('logSearch');
if (logSearch) {
    var logSearchTimer = null;
    logSearch.addEventListener('keyup', function() {
        clearTimeout(logSearchTimer);
        logSearchTimer = setTimeout(function() { loadLogs(1); }, 500);
    });
}
    
    function handleClearLogs() {
        Swal.fire({ title: 'Clear All Audit Logs?', text: 'This will permanently delete all audit log entries.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, clear all' }).then(function(result) {
            if (!result.isConfirmed) return;
            apiPost(appUrl + '/admin/api/audit-logs/clear', {}, function(d) { if (d.success) { loadLogs(); showToast(d.message); } else Swal.fire({ text: d.error, icon: 'error' }); });
        });
    }
    
    clearLogsBtn = document.getElementById('clearLogsBtn');
    if (clearLogsBtn) clearLogsBtn.addEventListener('click', handleClearLogs);
    
    clearLogsBtnMobile = document.getElementById('clearLogsBtnMobile');
    if (clearLogsBtnMobile) clearLogsBtnMobile.addEventListener('click', function() { mobileMenu.classList.add('hidden'); handleClearLogs(); });

    var clearLogsBtnCollapsed = document.getElementById('clearLogsBtnCollapsed');
    if (clearLogsBtnCollapsed) clearLogsBtnCollapsed.addEventListener('click', handleClearLogs);

    var exportBtn = document.getElementById('exportCSVBtn');
    if (exportBtn) exportBtn.addEventListener('click', function(e) { e.preventDefault(); startExport(); });
    
    var exportBtnMobile = document.getElementById('exportCSVBtnMobile');
    if (exportBtnMobile) exportBtnMobile.addEventListener('click', function(e) { e.preventDefault(); mobileMenu.classList.add('hidden'); startExport(); });

    var exportBtnCollapsed = document.getElementById('exportCSVBtnCollapsed');
    if (exportBtnCollapsed) exportBtnCollapsed.addEventListener('click', function(e) { e.preventDefault(); startExport(); });

    if (closeExportProgressBtn) {
        closeExportProgressBtn.addEventListener('click', function() {
            hideExportModal();
        });
    }

function openAuditDetailModal(logId) {
    if (auditDetailModal) {
        auditDetailModal.classList.remove('hidden');
    }
    if (auditDetailContent) {
        auditDetailContent.innerHTML = '<div class="text-center py-8 text-zinc-500">Loading...</div>';
    }
    
    fetch(appUrl + '/admin/api/audit-logs/detail?id=' + logId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            if (auditDetailContent) {
                auditDetailContent.innerHTML = '<div class="text-center py-8 text-red-500">' + (data.error || 'Failed to load details') + '</div>';
            }
            return;
        }
        
        var log = data.log;
        var html = '';
        
        html += '<div class="space-y-4">';
        
        html += '<div class="rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 p-4 space-y-3">';
        
        html += '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-3">';
        html += '<span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider shrink-0">Admin</span>';
        html += '<span class="text-sm font-semibold text-zinc-900 dark:text-white text-right break-words">' + (log.admin_name || 'Unknown') + '</span>';
        html += '</div>';
        
        html += '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-3">';
        html += '<span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider shrink-0">Action</span>';
        html += '<span class="text-sm font-semibold text-zinc-900 dark:text-white text-right break-words">' + log.action + '</span>';
        html += '</div>';
        
        html += '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-3">';
        html += '<span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider shrink-0">Entity</span>';
        html += '<span class="text-sm font-semibold text-zinc-900 dark:text-white text-right break-words">' + (log.entity_type || '-') + '</span>';
        html += '</div>';
        
        html += '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-3">';
        html += '<span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider shrink-0">Date</span>';
        html += '<span class="text-sm font-semibold text-zinc-900 dark:text-white text-right break-words">' + log.created_at + '</span>';
        html += '</div>';
        
        html += '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-3">';
        html += '<span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider shrink-0">IP Address</span>';
        html += '<span class="text-sm font-semibold text-zinc-900 dark:text-white text-right break-words">' + (log.ip_address || '-') + '</span>';
        html += '</div>';
        
        html += '<div class="flex flex-col gap-1">';
        html += '<span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Description</span>';
        html += '<span class="text-sm font-semibold text-zinc-900 dark:text-white break-words whitespace-normal">' + (log.description || '-') + '</span>';
        html += '</div>';
        
        html += '</div>';
        
        if (log.show_changes && log.changes && log.changes.length > 0) {
            html += '<div class="space-y-3">';
            html += '<p class="text-sm font-bold text-zinc-900 dark:text-white">Changes:</p>';
            
            log.changes.forEach(function(change) {
                if (log.action === 'CREATE') {
                    html += '<div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 p-3">';
                    html += '<p class="text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2 break-words">' + change.field + '</p>';
                    html += '<div class="rounded-xl bg-green-50 dark:bg-green-950/30 p-3 min-w-0">';
                    html += '<p class="text-[10px] font-semibold text-green-500 uppercase tracking-wider mb-1">Value</p>';
                    html += '<p class="text-sm text-zinc-900 dark:text-white break-words whitespace-normal">' + (change.new_value || '-') + '</p>';
                    html += '</div>';
                    html += '</div>';
                } else if (log.action === 'DELETE') {
                    html += '<div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 p-3">';
                    html += '<p class="text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2 break-words">' + change.field + '</p>';
                    html += '<div class="rounded-xl bg-red-50 dark:bg-red-950/30 p-3 min-w-0">';
                    html += '<p class="text-[10px] font-semibold text-red-500 uppercase tracking-wider mb-1">Value</p>';
                    html += '<p class="text-sm text-zinc-900 dark:text-white break-words whitespace-normal">' + (change.old_value || '-') + '</p>';
                    html += '</div>';
                    html += '</div>';
                } else {
                    html += '<div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 p-3">';
                    html += '<p class="text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2 break-words">' + change.field + '</p>';
                    html += '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">';
                    html += '<div class="rounded-xl bg-red-50 dark:bg-red-950/30 p-3 min-w-0">';
                    html += '<p class="text-[10px] font-semibold text-red-500 uppercase tracking-wider mb-1">Old</p>';
                    html += '<p class="text-sm text-zinc-900 dark:text-white break-words whitespace-normal">' + (change.old_value || '-') + '</p>';
                    html += '</div>';
                    html += '<div class="rounded-xl bg-green-50 dark:bg-green-950/30 p-3 min-w-0">';
                    html += '<p class="text-[10px] font-semibold text-green-500 uppercase tracking-wider mb-1">New</p>';
                    html += '<p class="text-sm text-zinc-900 dark:text-white break-words whitespace-normal">' + (change.new_value || '-') + '</p>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                }
            });
            
            html += '</div>';
        }
        
        html += '</div>';
        
        if (auditDetailContent) {
            auditDetailContent.innerHTML = safeHTML(html);
        }
    })
    .catch(function() {
        if (auditDetailContent) {
            auditDetailContent.innerHTML = '<div class="text-center py-8 text-red-500">Failed to load details</div>';
        }
    });
}

    function closeAuditDetailModal() {
        if (auditDetailModal) {
            auditDetailModal.classList.add('hidden');
        }
        document.body.style.overflow = '';
    }

    if (closeAuditDetailBtn) {
        closeAuditDetailBtn.addEventListener('click', closeAuditDetailModal);
    }
    if (closeAuditDetailFooterBtn) {
        closeAuditDetailFooterBtn.addEventListener('click', closeAuditDetailModal);
    }
    if (auditDetailBackdrop) {
        auditDetailBackdrop.addEventListener('click', closeAuditDetailModal);
    }

    function getActionBadge(action) {
        var colors = { CREATE: 'badge-green', UPDATE: 'badge-blue', DELETE: 'badge-red', CLEAR: 'badge-blue' };
        return '<span class="' + (colors[action] || 'badge-blue') + '">' + action + '</span>';
    }

    function loadLogs(page, search) {
        page = page || pages.logs; search = search || (logSearch ? logSearch.value : ''); pages.logs = page;
        var params = '?page=' + page + '&target=' + PAGE_TARGET; if (search) params += '&search=' + encodeURIComponent(search);
        apiGet(appUrl + '/admin/api/audit-logs' + params, function(data) {
            if (data.limit) limits.logs = data.limit;
            var h = '';
            if (!data.data.length) h = '<tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">No logs found</td></tr>';
            else {
                for (var i = 0; i < data.data.length; i++) {
                    var log = data.data[i];
                    var createdAt = log.created_at || '-';
                    h += '<tr class="border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer row-fixed" data-log-id="' + log.id + '">';
                    h += '<td class="cell-desktop px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">' + createdAt + '</td>';
                    h += '<td class="cell-desktop px-4 py-3 font-semibold text-zinc-900 dark:text-white"><span class="block truncate">' + (log.admin_name || 'Unknown') + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3">' + getActionBadge(log.action) + '</td>';
                    h += '<td class="cell-desktop px-4 py-3 text-zinc-600 dark:text-zinc-400"><span class="block truncate">' + (log.entity_type || '-') + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3 text-zinc-600 dark:text-zinc-400 text-xs"><span class="block truncate">' + (log.description || '-') + '</span></td>';
                    h += '<td class="cell-desktop px-4 py-3 text-xs text-zinc-400 dark:text-zinc-500 whitespace-nowrap">' + (log.ip_address || '-') + '</td>';
                    h += '<td class="cell-mobile px-4 py-3" colspan="6"><div class="space-y-2 mobile-card-fixed">';
                    h += '<div class="flex items-center justify-between min-w-0"><span class="font-semibold text-zinc-900 dark:text-white text-sm truncate">' + (log.admin_name || 'Unknown') + '</span>' + getActionBadge(log.action) + '</div>';
                    h += '<p class="text-xs text-zinc-500 truncate">' + (log.entity_type || '-') + ' - ' + (log.description || '-') + '</p>';
                    h += '<div class="flex items-center justify-between text-xs text-zinc-400 dark:text-zinc-500"><span class="truncate">' + createdAt + '</span><span class="truncate">IP: ' + (log.ip_address || '-') + '</span></div></td></tr>';
                }
            }
            document.getElementById('logsTableBody').innerHTML = safeHTML(h);
            
            document.getElementById('logsInfo').textContent = 'Page ' + data.page + ' of ' + data.total_pages + ' (' + data.total + ' total)';

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

            document.getElementById('logsPages').innerHTML = safeHTML(ph);

            var pageBtns = document.querySelectorAll('.page-btn');
            for (var l = 0; l < pageBtns.length; l++) {
                pageBtns[l].addEventListener('click', function() { loadLogs(parseInt(this.dataset.page)); });
            }

            if (data.pad) { applyBodyMinHeight('logsTableBody', limits.logs); } else { clearBodyMinHeight('logsTableBody'); }
            
            var logRows = document.querySelectorAll('#logsTableBody tr[data-log-id]');
            for (var n = 0; n < logRows.length; n++) {
                logRows[n].addEventListener('click', function() {
                    var logId = this.getAttribute('data-log-id');
                    document.body.style.overflow = 'hidden';
                    openAuditDetailModal(logId);
                });
            }
        });
    }

    window.addEventListener('beforeunload', function() {
        if (exportPollInterval) { clearInterval(exportPollInterval); exportPollInterval = null; }
        mobileMenuBtn = null; mobileMenu = null;
        logSearch = null; clearLogsBtn = null; clearLogsBtnMobile = null;
        exportBtn = null; exportBtnMobile = null;
        exportProgressModal = null; exportProgressBar = null; exportProgressText = null; exportProgressPercent = null;
        closeExportProgressBtn = null;
        clearLogsBtnCollapsed = null;
        exportBtnCollapsed = null;
        auditDetailModal = null;
        auditDetailBackdrop = null;
        auditDetailContent = null;
        closeAuditDetailBtn = null;
        closeAuditDetailFooterBtn = null;
        document.body.style.overflow = '';
    });

    loadLogs();
})();