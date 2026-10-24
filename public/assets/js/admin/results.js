// results.js
(function() {
    'use strict';

    // ===== GLOBALS =====
    var appUrl, csrfToken, candidateImagesUrl;
    var autoRefresh = true;
    var refreshInterval = null;
    var charts = {};
    var isFirstLoad = true;
    var container = null;
    var mobileMenuBtn = null, mobileMenu = null;
    var menuIconOpen = null, menuIconClose = null;
    var exportPollInterval = null;
    var exportProgressModal = null, exportProgressBar = null, exportProgressText = null,
        exportProgressPercent = null, closeExportProgressBtn = null;
    var scrollTopFab = null;
    var mainScrollContainer = null;

    // ===== PRIVACY =====
    var resultsHidden = true;
    try {
        resultsHidden = sessionStorage.getItem('ssc_results_shown') !== '1';
    } catch (e) {
        resultsHidden = true;
    }

    var latestData = null;
    var toggleResultsPrivacy = null, toggleResultsPrivacyLabel = null, toggleResultsPrivacyIcon = null;
    var toggleResultsPrivacyCollapsed = null, toggleResultsPrivacyIconCollapsed = null;
    var toggleResultsPrivacyMobile = null, toggleResultsPrivacyMobileText = null, toggleResultsPrivacyMobileIcon = null;

    // ===== VOTE LOOKUP =====
    var openVoteLookupBtn = null, openVoteLookupBtnCollapsed = null, openVoteLookupBtnMobile = null;
    var voteLookupModal = null, voteLookupBackdrop = null, closeVoteLookupBtn = null;
    var voteLookupContent = null, voteLookupSearchInput = null, voteLookupResults = null;
    var voteLookupBackBtn = null, voteLookupModalTitle = null;
    var lookupSearchTimeout = null;
    var lookupPreviousView = null;

    // ===== COLLAPSED SIDEBAR =====
    var toggleRefreshCollapsed = null, exportCSVBtnCollapsed = null, deleteElectionDataCollapsed = null;

    // ===== HELPERS =====
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function el(tag, className, attrs) {
        var e = document.createElement(tag);
        if (className) e.className = className;
        if (attrs) {
            for (var k in attrs) {
                if (!Object.prototype.hasOwnProperty.call(attrs, k)) continue;
                if (k === 'textContent') e.textContent = attrs[k];
                else if (k === 'style') { for (var s in attrs[k]) e.style[s] = attrs[k][s]; }
                else if (k === 'src') e.src = attrs[k];
                else if (k === 'alt') e.alt = attrs[k];
                else if (k === 'id') e.id = attrs[k];
                else e.setAttribute(k, attrs[k]);
            }
        }
        return e;
    }

    // ===== MOBILE MENU =====
    function updateMenuIcon() {
        if (!mobileMenu || !menuIconOpen || !menuIconClose) return;
        if (mobileMenu.classList.contains('hidden')) {
            menuIconOpen.classList.remove('hidden');
            menuIconClose.classList.add('hidden');
        } else {
            menuIconOpen.classList.add('hidden');
            menuIconClose.classList.remove('hidden');
        }
    }

    function openMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.remove('hidden');
        mobileMenu.offsetHeight;
        mobileMenu.classList.remove('max-h-0', 'opacity-0');
        mobileMenu.classList.add('max-h-[calc(100dvh-8rem)]', 'opacity-100');
        updateMenuIcon();
    }

    function closeMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.remove('max-h-[calc(100dvh-8rem)]', 'opacity-100');
        mobileMenu.classList.add('max-h-0', 'opacity-0');
        setTimeout(function() {
            if (!mobileMenu) return;
            mobileMenu.classList.add('hidden');
            updateMenuIcon();
        }, 300);
    }

    // ===== PRIVACY FUNCTIONS =====
    function syncPrivacyToggleText() {
        var label = resultsHidden ? 'Show Results' : 'Hide Results';
        var mobileLabel = resultsHidden ? 'Show' : 'Hide';

        if (toggleResultsPrivacyLabel) toggleResultsPrivacyLabel.textContent = label;
        if (toggleResultsPrivacyMobileText) toggleResultsPrivacyMobileText.textContent = mobileLabel;

        var eyeOpenPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>';
        var eyeClosedPath = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
        var path = resultsHidden ? eyeOpenPath : eyeClosedPath;

        if (toggleResultsPrivacyIcon) toggleResultsPrivacyIcon.innerHTML = path;
        if (toggleResultsPrivacyIconCollapsed) toggleResultsPrivacyIconCollapsed.innerHTML = path;
        if (toggleResultsPrivacyMobileIcon) toggleResultsPrivacyMobileIcon.innerHTML = path;
    }

    function destroyAllCharts() {
        for (var key in charts) {
            if (Object.prototype.hasOwnProperty.call(charts, key) && charts[key]) {
                try { charts[key].destroy(); } catch (e) {}
                delete charts[key];
            }
        }
        charts = {};
    }

    function maskAllResults() {
        var totalVotesEl = document.getElementById('totalVotes');
        if (totalVotesEl) totalVotesEl.textContent = '******';

        var voteSpans = document.querySelectorAll('[data-votes]');
        for (var i = 0; i < voteSpans.length; i++) {
            voteSpans[i].textContent = '(******)';
        }

        var countSpans = document.querySelectorAll('[data-count]');
        for (var j = 0; j < countSpans.length; j++) {
            countSpans[j].textContent = '******';
        }

        var winningBadges = document.querySelectorAll('[data-winning-badge]');
        for (var wb = 0; wb < winningBadges.length; wb++) {
            winningBadges[wb].parentElement.removeChild(winningBadges[wb]);
        }

        var chartWraps = document.querySelectorAll('[data-chart-wrap]');
        for (var k = 0; k < chartWraps.length; k++) {
            var canvas = chartWraps[k].querySelector('canvas');
            if (canvas) {
                chartWraps[k].innerHTML = '';
                var placeholder = document.createElement('p');
                placeholder.className = 'text-sm text-zinc-500 dark:text-zinc-400 text-center py-8';
                placeholder.textContent = 'HIDDEN';
                placeholder.setAttribute('data-privacy-placeholder', 'true');
                chartWraps[k].appendChild(placeholder);
            }
        }
    }

    function unmaskAllResults() {
        if (!latestData) return;

        var totalVotesEl = document.getElementById('totalVotes');
        if (totalVotesEl && latestData.total_votes !== undefined) {
            totalVotesEl.textContent = latestData.total_votes.toLocaleString();
        }

        if (latestData.positions) {
            for (var i = 0; i < latestData.positions.length; i++) {
                var pos = latestData.positions[i];
                var voteSpan = document.querySelector('[data-votes="' + pos.id + '"]');
                if (voteSpan) {
                    voteSpan.textContent = '(' + pos.total_votes + ' vote' + (pos.total_votes !== 1 ? 's' : '') + ')';
                }
            }
        }

        if (latestData.positions) {
            for (var j = 0; j < latestData.positions.length; j++) {
                var posCandidates = latestData.positions[j].candidates || [];
                for (var k = 0; k < posCandidates.length; k++) {
                    var c = posCandidates[k];
                    var countSpan = document.querySelector('[data-count="' + c.id + '"]');
                    if (countSpan) {
                        countSpan.textContent = c.vote_count + ' vote' + (c.vote_count !== 1 ? 's' : '');
                    }
                    // Insert winning badge for desktop cards (inside nameRow) when revealed
                    if (c.is_winning && !resultsHidden) {
                        var desktopRow = document.querySelector('[data-candidate="' + c.id + '"]');
                        if (desktopRow) {
                            var rowInfo = desktopRow.querySelector('.flex-1');
                            if (rowInfo) {
                                var nameRow = rowInfo.querySelector('.flex-col.items-center.gap-1');
                                if (nameRow && !nameRow.querySelector('[data-winning-badge="' + c.id + '"]')) {
                                    var wb = document.createElement('span');
                                    wb.className = 'mx-auto w-fit self-center text-xs font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300/40';
                                    wb.setAttribute('data-winning-badge', c.id);
                                    wb.textContent = 'winning';
                                    wb.style.minWidth = '7ch';
                                    nameRow.appendChild(wb);
                                }
                            }
                        }
                        // Mobile winning badge update
                        var mobileVoteWrap = document.querySelector('.md\\:hidden [data-candidate="' + c.id + '"] .flex.items-center.gap-2');
                        if (mobileVoteWrap && !mobileVoteWrap.querySelector('[data-winning-badge="' + c.id + '"]')) {
                            mobileVoteWrap.innerHTML = '<span class="text-xs font-medium text-zinc-500 dark:text-zinc-400" data-count="' + c.id + '">' + (c.vote_count + ' vote' + (c.vote_count !== 1 ? 's' : '')) + '</span><span data-winning-badge="' + c.id + '" class="self-start text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300/40">winning</span>';
                        }
                    }
                }
            }
        }

        var placeholders = document.querySelectorAll('[data-privacy-placeholder]');
        for (var p = 0; p < placeholders.length; p++) {
            var wrap = placeholders[p].parentElement;
            if (wrap) {
                wrap.innerHTML = '';
                var canvas = document.createElement('canvas');
                var posId = wrap.parentElement ? wrap.parentElement.getAttribute('data-position') : null;
                if (posId) canvas.id = 'chart_' + posId;
                wrap.appendChild(canvas);
            }
        }

        if (latestData.positions) {
            for (var cIdx = 0; cIdx < latestData.positions.length; cIdx++) {
                var posC = latestData.positions[cIdx].candidates || [];
                if (posC.length > 0) initChart(latestData.positions[cIdx]);
            }
        }

        // Sync winning badges after unmasking
        if (latestData.positions) {
            for (var s = 0; s < latestData.positions.length; s++) {
                var sCands = latestData.positions[s].candidates || [];
                for (var t = 0; t < sCands.length; t++) {
                    var sc = sCands[t];
                    var sRow = document.querySelector('[data-candidate="' + sc.id + '"]');
                    if (sRow) {
                        var sInfo = sRow.querySelector('.flex-1');
                        if (sc.is_winning) {
                            if (sInfo && !sInfo.querySelector('[data-winning-badge="' + sc.id + '"]')) {
                                var swb = el('span', 'ml-2 w-fit self-start text-xs font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300/40');
                                swb.setAttribute('data-winning-badge', sc.id);
                                swb.textContent = 'winning';
                                swb.setAttribute('style', 'min-width: 7ch;');
                                var sCount = sInfo.querySelector('[data-count="' + sc.id + '"]');
                                if (sCount) sInfo.insertBefore(swb, sCount);
                                else sInfo.appendChild(swb);
                            }
                        } else {
                            if (sInfo) {
                                var sew = sInfo.querySelector('[data-winning-badge="' + sc.id + '"]');
                                if (sew) sInfo.removeChild(sew);
                            }
                        }
                    }
                }
            }
        }
    }

    function promptForPassword(callback) {
        if (typeof Swal === 'undefined') {
            var pw = window.prompt('Enter your password to show results:');
            if (pw) callback(pw);
            return;
        }
        Swal.fire({
            title: 'Verify Password',
            text: 'Enter your current password to show the election results.',
            input: 'password',
            inputPlaceholder: 'Enter password',
            inputAttributes: { autocomplete: 'current-password' },
            showCancelButton: true,
            confirmButtonText: 'Verify',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed && result.value) callback(result.value);
        });
    }

    function verifyPassword(password, onSuccess, onFail) {
        var fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fd.append('password', password);

        fetch(appUrl + '/admin/verify-password', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                resultsHidden = false;
                try { sessionStorage.setItem('ssc_results_shown', '1'); } catch (e) {}
                syncPrivacyToggleText();
                unmaskAllResults();
            } else if (data.remaining_seconds) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Too Many Attempts',
                        text: 'Please wait ' + data.remaining_seconds + ' seconds before trying again.',
                        icon: 'warning'
                    });
                } else {
                    alert('Too many attempts. Please wait ' + data.remaining_seconds + ' seconds.');
                }
            } else if (onFail) {
                onFail(data.message || 'Incorrect password.');
            }
        })
        .catch(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ text: 'Unable to verify your password. Please try again.', icon: 'error' });
            } else {
                alert('Unable to verify your password. Please try again.');
            }
        });
    }

    function togglePrivacy() {
        if (!resultsHidden) {
            resultsHidden = true;
            try { sessionStorage.removeItem('ssc_results_shown'); } catch (e) {}
            syncPrivacyToggleText();
            destroyAllCharts();
            maskAllResults();
        } else {
            promptForPassword(function(password) {
                verifyPassword(password, null, function(msg) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            text: msg,
                            icon: 'error',
                            showCancelButton: true,
                            confirmButtonText: 'Try Again',
                            cancelButtonText: 'Cancel'
                        }).then(function(retryResult) {
                            if (retryResult.isConfirmed) {
                                promptForPassword(function(newPassword) {
                                    verifyPassword(newPassword, null, null);
                                });
                            }
                        });
                    } else {
                        alert(msg);
                    }
                });
            });
        }
    }

    // ===== VOTE LOOKUP =====
    function resetLookupModal() {
        if (voteLookupSearchInput) voteLookupSearchInput.value = '';
        if (voteLookupResults) voteLookupResults.innerHTML = '<p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-8">Type a name or email to search</p>';
        if (voteLookupBackBtn) voteLookupBackBtn.classList.add('hidden');
        if (voteLookupModalTitle) voteLookupModalTitle.textContent = 'Vote Lookup';
        lookupPreviousView = 'results';
    }

    function openVoteLookupModal() {
        if (!voteLookupModal) return;
        resetLookupModal();
        voteLookupModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (voteLookupSearchInput) {
            setTimeout(function() { if (voteLookupSearchInput) voteLookupSearchInput.focus(); }, 100);
        }
    }

    function closeVoteLookupModal() {
        if (voteLookupModal) voteLookupModal.classList.add('hidden');
        document.body.style.overflow = '';
        if (lookupSearchTimeout) {
            clearTimeout(lookupSearchTimeout);
            lookupSearchTimeout = null;
        }
    }

    function showLookupResults(query) {
        if (!voteLookupResults) return;
        voteLookupResults.innerHTML = '<p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-8">Searching...</p>';

        fetch(appUrl + '/admin/api/votes/search?q=' + encodeURIComponent(query), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !voteLookupResults) return;

            if (!data.votes || data.votes.length === 0) {
                voteLookupResults.innerHTML = '<p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-8">No results found</p>';
                return;
            }

            var html = '<p class="text-xs text-zinc-400 dark:text-zinc-500 px-2 pb-2">' + data.votes.length + ' result' + (data.votes.length !== 1 ? 's' : '') + (data.votes.length === 20 ? ' (max 20 shown)' : '') + '</p>';

            for (var i = 0; i < data.votes.length; i++) {
                var v = data.votes[i];
                var shortId = v.voter_identifier ? v.voter_identifier.substring(0, 8) : '—';
                html += '<div class="px-3 py-2.5 sm:px-4 sm:py-3 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors duration-200 cursor-pointer border border-zinc-100 dark:border-zinc-800" data-voter-id="' + escapeHtml(v.voter_identifier) + '">';
                html += '<div class="flex items-center justify-between">';
                html += '<p class="text-sm font-semibold text-zinc-900 dark:text-white">' + escapeHtml(v.voter_name) + '</p>';
                html += '<span class="text-[11px] font-mono text-zinc-400 dark:text-zinc-500">#' + escapeHtml(shortId) + '</span>';
                html += '</div>';
                html += '<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">' + escapeHtml(v.email) + '</p>';
                html += '<p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-1">' + escapeHtml(v.voted_at) + '</p>';
                html += '</div>';
            }

            voteLookupResults.innerHTML = html;

            var items = voteLookupResults.querySelectorAll('[data-voter-id]');
            for (var j = 0; j < items.length; j++) {
                items[j].addEventListener('click', function() {
                    var vid = this.getAttribute('data-voter-id');
                    if (vid) showLookupDetail(vid);
                });
            }

            lookupPreviousView = 'results';
        })
        .catch(function() {
            if (voteLookupResults) {
                voteLookupResults.innerHTML = '<p class="text-sm text-red-500 text-center py-8">Search failed</p>';
            }
        });
    }

    function showLookupDetail(voterIdentifier) {
        if (!voteLookupContent || !voteLookupResults) return;

        var resultsHTML = voteLookupResults.innerHTML;
        voteLookupResults.innerHTML = '<p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-8">Loading...</p>';
        if (voteLookupBackBtn) voteLookupBackBtn.classList.remove('hidden');
        if (voteLookupModalTitle) voteLookupModalTitle.textContent = 'Vote Details';

        fetch(appUrl + '/admin/api/votes/detail?voter_id=' + encodeURIComponent(voterIdentifier), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !voteLookupResults) {
                if (voteLookupResults) voteLookupResults.innerHTML = '<p class="text-sm text-red-500 text-center py-8">Failed to load details</p>';
                return;
            }

            var v = data.vote;
            var html = '';

            html += '<div class="mb-4 p-3 sm:p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50">';
            html += '<p class="text-sm font-semibold text-zinc-900 dark:text-white">' + escapeHtml(v.voter_name) + '</p>';
            html += '<p class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">' + escapeHtml(v.voter_identifier) + '</p>';
            html += '<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Email: ' + escapeHtml(v.email) + '</p>';
            html += '<p class="text-xs text-zinc-500 dark:text-zinc-400">Voted: ' + escapeHtml(v.voted_at) + '</p>';
            html += '</div>';

            if (v.positions && v.positions.length > 0) {
                html += '<div class="space-y-2 sm:space-y-3">';
                for (var i = 0; i < v.positions.length; i++) {
                    var pos = v.positions[i];
                    html += '<div class="flex items-start gap-2 sm:gap-3 p-2.5 sm:p-3 rounded-xl border border-zinc-100 dark:border-zinc-800">';
                    if (pos.image_path) {
                        html += '<img src="' + candidateImagesUrl + '/' + escapeHtml(pos.image_path) + '" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover shrink-0" alt="">';
                    } else {
                        html += '<div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-zinc-100 dark:bg-zinc-800 shrink-0 flex items-center justify-center"><svg class="w-4 h-4 sm:w-5 sm:h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>';
                    }
                    html += '<div class="min-w-0">';
                    html += '<p class="text-[11px] sm:text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">' + escapeHtml(pos.position_name) + '</p>';
                    html += '<p class="text-sm sm:text-base font-semibold text-zinc-900 dark:text-white">' + escapeHtml(pos.candidate_name) + '</p>';
                    if (pos.partylist_name) {
                        html += '<span data-partylist-badge="' + escapeHtml(pos.partylist_name) + '" class="text-[10px] sm:text-[11px] mt-1 inline-block pl-badge border border-zinc-300 dark:border-zinc-600 rounded-lg px-2 py-1">' + escapeHtml(pos.partylist_name) + '</span>';
                    }
                    html += '</div></div>';
                }
                html += '</div>';
            }

            voteLookupResults.innerHTML = html;
            lookupPreviousView = 'detail';
            voteLookupResults.setAttribute('data-results-html', resultsHTML);
        })
        .catch(function() {
            if (voteLookupResults) {
                voteLookupResults.innerHTML = '<p class="text-sm text-red-500 text-center py-8">Failed to load details</p>';
            }
        });
    }

    function goBackToLookupResults() {
        if (!voteLookupResults || lookupPreviousView !== 'detail') return;
        var resultsHTML = voteLookupResults.getAttribute('data-results-html');
        if (resultsHTML) {
            voteLookupResults.innerHTML = resultsHTML;
            var items = voteLookupResults.querySelectorAll('[data-voter-id]');
            for (var j = 0; j < items.length; j++) {
                items[j].addEventListener('click', function() {
                    var vid = this.getAttribute('data-voter-id');
                    if (vid) showLookupDetail(vid);
                });
            }
        }
        if (voteLookupBackBtn) voteLookupBackBtn.classList.add('hidden');
        if (voteLookupModalTitle) voteLookupModalTitle.textContent = 'Vote Lookup';
        lookupPreviousView = 'results';
    }

    // ===== EXPORT =====
    function showExportModal() {
        if (!exportProgressModal) return;
        exportProgressModal.classList.remove('hidden');
        if (exportProgressBar) exportProgressBar.className = 'h-full bg-blue-600 rounded-full transition-all duration-300 w-0';
        if (exportProgressText) exportProgressText.textContent = 'Preparing export...';
        if (exportProgressPercent) exportProgressPercent.textContent = '0%';
        if (closeExportProgressBtn) closeExportProgressBtn.classList.add('hidden');
    }

    function hideExportModal() {
        if (exportProgressModal) exportProgressModal.classList.add('hidden');
        if (exportPollInterval) {
            clearInterval(exportPollInterval);
            exportPollInterval = null;
        }
    }

    function updateExportProgress(progress, processed, total, status) {
        if (exportProgressBar) {
            var pct = Math.min(100, Math.max(0, progress || 0));
            exportProgressBar.style.width = pct + '%';
            exportProgressBar.className = 'h-full bg-blue-600 rounded-full transition-all duration-300';
        }
        if (exportProgressText && total > 0) {
            exportProgressText.textContent = (status === 'complete') ? 'Export complete!' : ('Processing row ' + processed + ' of ' + total + '...');
        }
        if (exportProgressPercent) exportProgressPercent.textContent = progress + '%';
        if (status === 'complete' && closeExportProgressBtn) closeExportProgressBtn.classList.remove('hidden');
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

        fetch(appUrl + '/admin/api/results/export', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                hideExportModal();
                if (typeof Swal !== 'undefined') Swal.fire({ text: data.error || 'Export failed', icon: 'error' });
                return;
            }

            var exportId = data.export_id;

            exportPollInterval = setInterval(function() {
                fetch(appUrl + '/admin/api/results/export-progress?export_id=' + encodeURIComponent(exportId), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(progressData) {
                    if (!progressData.success) {
                        clearInterval(exportPollInterval);
                        exportPollInterval = null;
                        hideExportModal();
                        if (typeof Swal !== 'undefined') Swal.fire({ text: progressData.error || 'Export failed', icon: 'error' });
                        return;
                    }

                    updateExportProgress(progressData.progress, progressData.processed, progressData.total, progressData.status);

                    if (progressData.status === 'complete' && progressData.download_url) {
                        clearInterval(exportPollInterval);
                        exportPollInterval = null;
                        updateExportProgress(100, progressData.processed, progressData.total, 'complete');
                        triggerDownload(progressData.download_url);
                        setTimeout(hideExportModal, 1000);
                    }
                })
                .catch(function() {
                    clearInterval(exportPollInterval);
                    exportPollInterval = null;
                    hideExportModal();
                    if (typeof Swal !== 'undefined') Swal.fire({ text: 'Failed to check export progress', icon: 'error' });
                });
            }, 500);
        })
        .catch(function() {
            hideExportModal();
            if (typeof Swal !== 'undefined') Swal.fire({ text: 'Failed to start export', icon: 'error' });
        });
    }

    // ===== RESULTS =====
    function loadResults() {
        fetch(appUrl + '/admin/api/results', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || !data.success) return;

                latestData = data;

                var tv = document.getElementById('totalVotes');
                if (tv) tv.textContent = resultsHidden ? '******' : (data.total_votes || 0).toLocaleString();
                var lu = document.getElementById('lastUpdated');
                if (lu) lu.textContent = 'Updated: ' + (data.generated_at || '');

                if (isFirstLoad) {
                    buildFullLayout(data);
                    isFirstLoad = false;
                    if (resultsHidden) maskAllResults();
                } else {
                    updateData(data);
                }
            })
            .catch(function(err) {
                console.error('Failed to load results:', err);
            });
    }

    function buildFullLayout(data) {
        if (!container) return;

        while (container.firstChild) container.removeChild(container.firstChild);

        if (!data.positions || data.positions.length === 0) {
            var emptyCard = el('div', 'card text-center py-12');
            emptyCard.appendChild(el('p', 'text-zinc-500', { textContent: 'No results available' }));
            container.appendChild(emptyCard);
            return;
        }

        for (var i = 0; i < data.positions.length; i++) {
            var pos = data.positions[i];
            var cands = pos.candidates || [];
            var totalVotes = pos.total_votes || 0;

            var card = el('div', 'card');
            card.setAttribute('data-position', pos.id);

            var header = el('h3', 'text-xl font-bold text-zinc-900 dark:text-white mb-4 pb-2 border-b border-zinc-200/60 dark:border-zinc-800/60');
            header.textContent = pos.name + ' ';
            var voteSpan = el('span', 'text-sm font-normal text-zinc-500');
            voteSpan.setAttribute('data-votes', pos.id);
            voteSpan.textContent = resultsHidden ? '(******)' : ('(' + totalVotes + ' vote' + (totalVotes !== 1 ? 's' : '') + ')');
            header.appendChild(voteSpan);
            card.appendChild(header);

            if (cands.length === 0) {
                card.appendChild(el('p', 'text-zinc-500 text-sm', { textContent: 'No candidates' }));
            } else {
                var chartWrap = el('div', 'mb-4');
                chartWrap.style.height = '200px';
                chartWrap.setAttribute('data-chart-wrap', pos.id);

                if (resultsHidden) {
                    var placeholder = el('p', 'text-sm text-zinc-500 dark:text-zinc-400 text-center py-8', { textContent: 'HIDDEN' });
                    placeholder.setAttribute('data-privacy-placeholder', 'true');
                    chartWrap.appendChild(placeholder);
                } else {
                    var canvas = el('canvas', null, { id: 'chart_' + pos.id });
                    chartWrap.appendChild(canvas);
                }
                card.appendChild(chartWrap);

                var grid = el('div', 'hidden md:grid md:grid-cols-2 lg:grid-cols-3 gap-4');
                var mobileList = el('div', 'md:hidden space-y-0');
                for (var j = 0; j < cands.length; j++) {
                    var c = cands[j];
                    var row = el('div', 'flex flex-col items-center text-center gap-4 p-4 bg-white dark:bg-zinc-900 rounded-2xl shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-800');
                    if (c.is_winning && !resultsHidden) {
                        // winning badge applied after vote count instead of glow
                    }
                    row.setAttribute('data-candidate', c.id);

                    var imgWrap = el('div', 'w-full h-32 md:h-48 lg:h-56 bg-zinc-100 dark:bg-zinc-800 rounded-3xl overflow-hidden flex items-center justify-center');
                    if (c.image_path) {
                        imgWrap.appendChild(el('img', 'w-full h-full max-w-full max-h-full object-contain', { src: candidateImagesUrl + '/' + c.image_path, alt: c.name }));
                    } else {
                        var phSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        phSvg.setAttribute('class', 'w-16 h-16 md:w-20 md:h-20 text-zinc-400');
                        phSvg.setAttribute('fill', 'none');
                        phSvg.setAttribute('stroke', 'currentColor');
                        phSvg.setAttribute('viewBox', '0 0 24 24');
                        phSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>';
                        imgWrap.appendChild(phSvg);
                    }
                    row.appendChild(imgWrap);

                    var infoWrap = el('div', 'flex-1 min-w-0 flex flex-col items-center text-center gap-2');

                    var nameRow = el('div', 'flex flex-col items-center gap-1');
                    nameRow.appendChild(el('span', 'font-semibold text-zinc-900 dark:text-white text-base md:text-lg', { textContent: c.name }));
                    if (c.partylist_name) {
                        var plSpan = el('span', 'text-sm self-center pl-badge border border-zinc-300 dark:border-zinc-600 rounded-lg px-[6px] py-[2px] rounded-[6px] inline-block', {
                            textContent: c.partylist_name,
                            'data-partylist-badge': c.partylist_name,
                            'data-pl-color': c.partylist_badge_color || '#1e40af',
                            'data-pl-text': c.partylist_badge_text_color || '#ffffff'
                        });
                        nameRow.appendChild(plSpan);
                    }
                    infoWrap.appendChild(nameRow);

                    var voteCountText = el('span', 'text-base md:text-lg font-semibold text-zinc-900 dark:text-white shrink-0');
                    voteCountText.setAttribute('data-count', c.id);
                    voteCountText.textContent = resultsHidden ? '******' : (c.vote_count + ' vote' + (c.vote_count !== 1 ? 's' : ''));
                    if (c.is_winning && !resultsHidden) {
                        var winningBadge = el('span', 'mx-auto w-fit self-center text-xs font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300/40');
                        winningBadge.setAttribute('data-winning-badge', c.id);
                        winningBadge.textContent = 'winning';
                        winningBadge.setAttribute('style', 'min-width: 7ch;');
                        nameRow.appendChild(winningBadge);
                    }

                    infoWrap.appendChild(voteCountText);

                    row.appendChild(infoWrap);
                    grid.appendChild(row);

                    // Mobile compact row
                    var mobileRow = el('div', 'flex items-start gap-3 py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0');
                    mobileRow.setAttribute('data-candidate', c.id);
                    var mobileImgWrap = document.createElement('div');
                    mobileImgWrap.className = 'shrink-0 w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden flex items-center justify-center';
                    if (c.image_path) {
                        mobileImgWrap.innerHTML = '<img src="' + escapeHtml(candidateImagesUrl + '/' + c.image_path) + '" class="w-full h-full object-cover" alt="" />';
                    } else {
                        mobileImgWrap.innerHTML = '<svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
                    }
                    mobileRow.appendChild(mobileImgWrap);
                    var mobileInfo = el('div', 'flex-1 min-w-0 flex flex-col gap-3');
                    var mobileNameRow = el('div', 'flex flex-col gap-1');
                    mobileNameRow.appendChild(el('span', 'font-semibold text-zinc-900 dark:text-white text-sm', { textContent: c.name }));
                    if (c.partylist_name) {
                        var mobilePlSpan = el('span', 'text-[10px] inline-block pl-badge border border-zinc-300 dark:border-zinc-600 rounded px-1.5 py-0.5 self-start', {
                            textContent: c.partylist_name,
                            'data-partylist-badge': c.partylist_name,
                            'data-pl-color': c.partylist_badge_color || '#1e40af',
                            'data-pl-text': c.partylist_badge_text_color || '#ffffff'
                        });
                        mobileNameRow.appendChild(mobilePlSpan);
                    }
                    mobileInfo.appendChild(mobileNameRow);
                    var voteWrap = document.createElement('div');
                    voteWrap.className = 'flex items-center gap-2';
                    voteWrap.innerHTML = '<span class="text-xs font-medium text-zinc-500 dark:text-zinc-400" data-count="' + c.id + '">' + (resultsHidden ? '****** votes' : (c.vote_count + ' vote' + (c.vote_count !== 1 ? 's' : '')) ) + '</span>' + (c.is_winning && !resultsHidden ? '<span data-winning-badge="' + c.id + '" class="self-start text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300/40">winning</span>' : '');
                    mobileInfo.appendChild(voteWrap);
                    mobileRow.appendChild(mobileInfo);
                    mobileList.appendChild(mobileRow);
                }
                card.appendChild(grid);
                card.appendChild(mobileList);
            }

            container.appendChild(card);
        }

        if (!resultsHidden) {
            for (var k2 = 0; k2 < data.positions.length; k2++) {
                var p2 = data.positions[k2];
                if ((p2.candidates || []).length === 0) continue;
                initChart(p2);
            }
        }
    }

    function initChart(pos) {
        if (typeof Chart === 'undefined') return;
        var ctx = document.getElementById('chart_' + pos.id);
        if (!ctx) return;

        var labels = [];
        var votes = [];
        var cands = pos.candidates || [];
        for (var i = 0; i < cands.length; i++) {
            labels.push(cands[i].name.length > 20 ? cands[i].name.substring(0, 20) + '...' : cands[i].name);
            votes.push(cands[i].vote_count);
        }
        var colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'];
        var barColors = [];
        for (var b = 0; b < cands.length; b++) {
            barColors.push(cands[b].partylist_badge_color || colors[b % colors.length]);
        }
        try {
            charts[pos.id] = new Chart(ctx, {
                type: 'bar',
                data: { labels: labels, datasets: [{ label: 'Votes', data: votes, backgroundColor: barColors, borderRadius: 4 }] },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } }, y: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (e) {
            console.error('Chart init failed for position', pos.id, e);
        }
    }

    function updateData(data) {
        if (resultsHidden) return;

        for (var i = 0; i < data.positions.length; i++) {
            var pos = data.positions[i];
            var cands = pos.candidates || [];

            var votesEl = document.querySelector('[data-votes="' + pos.id + '"]');
            if (votesEl) votesEl.textContent = '(' + pos.total_votes + ' vote' + (pos.total_votes !== 1 ? 's' : '') + ')';

            if (cands.length > 0) {
                if (charts[pos.id]) {
                    var labels = [];
                    var voteData = [];
                    for (var j = 0; j < cands.length; j++) {
                        labels.push(cands[j].name.length > 20 ? cands[j].name.substring(0, 20) + '...' : cands[j].name);
                        voteData.push(cands[j].vote_count);
                    }
                    charts[pos.id].data.labels = labels;
                    charts[pos.id].data.datasets[0].data = voteData;
                    var colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'];
                    var barColors = [];
                    for (var m = 0; m < cands.length; m++) barColors.push(cands[m].partylist_badge_color || colors[m % colors.length]);
                    charts[pos.id].data.datasets[0].backgroundColor = barColors;
                    charts[pos.id].update();
                } else {
                    initChart(pos);
                }
            }

            for (var k = 0; k < cands.length; k++) {
                var c = cands[k];
                var counts = document.querySelectorAll('[data-count="' + c.id + '"]');
                for (var n = 0; n < counts.length; n++) counts[n].textContent = c.vote_count + ' vote' + (c.vote_count !== 1 ? 's' : '');

                var rows = document.querySelectorAll('[data-candidate="' + c.id + '"]');
                for (var r = 0; r < rows.length; r++) {
                    var row = rows[r];
                    // Skip mobile rows; mobile handled separately
                    if (row.closest && row.closest('.md\\:hidden')) continue;
                    if (c.is_winning) {
                        // winning badge applied after vote count instead of glow
                        var infoWrap = row.querySelector('.flex-1');
                        var nameRow = infoWrap ? infoWrap.querySelector('.flex-col.items-center.gap-1') : null;
                        var targetWrap = nameRow || infoWrap;
                        if (targetWrap && !targetWrap.querySelector('[data-winning-badge="' + c.id + '"]')) {
                            var wb = el('span', 'mx-auto w-fit self-center text-xs font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300/40');
                            wb.setAttribute('data-winning-badge', c.id);
                            wb.textContent = 'winning';
                            wb.setAttribute('style', 'min-width: 7ch;');
                            targetWrap.appendChild(wb);
                        }
                    } else {
                        if (row.closest && row.closest('.md\\:hidden')) continue;
                        var infoWrap2 = row.querySelector('.flex-1');
                        if (infoWrap2) {
                            var nameRow2 = infoWrap2.querySelector('.flex-col.items-center.gap-1');
                            var targetWrap2 = nameRow2 || infoWrap2;
                            var existing = targetWrap2.querySelector('[data-winning-badge="' + c.id + '"]');
                            if (existing && existing.parentNode === targetWrap2) targetWrap2.removeChild(existing);
                        }
                        row.style.padding = '';
                        row.style.margin = '';
                        row.style.borderRadius = '';
                        row.style.boxShadow = '';
                    }
                }
                // Mobile refresh: rebuild mobile row data
                var mobileRows2 = document.querySelectorAll('[data-candidate="' + c.id + '"]');
                for (var mr2 = 0; mr2 < mobileRows2.length; mr2++) {
                    var mR2 = mobileRows2[mr2];
                    if (mR2.closest && mR2.closest('.md\\:hidden')) {
                        var mVoteWrap2 = mR2.querySelector('[data-count="' + c.id + '"]');
                        if (mVoteWrap2 && mVoteWrap2.parentNode) {
                            var parentWrap = mVoteWrap2.parentNode;
                            parentWrap.innerHTML = '<span class="text-xs font-medium text-zinc-500 dark:text-zinc-400" data-count="' + c.id + '">' + (resultsHidden ? '****** votes' : (c.vote_count + ' vote' + (c.vote_count !== 1 ? 's' : '')) ) + '</span>' + (c.is_winning && !resultsHidden ? '<span data-winning-badge="' + c.id + '" class="self-start text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-amber-400/20 text-amber-700 dark:text-amber-300 border border-amber-300/40">winning</span>' : '');
                        }
                    }
                }
            }
        }
    }

    // ===== TOGGLE / AUTO-REFRESH =====
    function syncToggleText() {
        var text = 'Auto-refresh: ' + (autoRefresh ? 'ON' : 'OFF');
        var mobileText = 'Auto: ' + (autoRefresh ? 'ON' : 'OFF');
        var dt = document.getElementById('toggleRefreshLabel');
        var icon = document.getElementById('toggleRefreshIcon');
        var collapsedIcon = document.getElementById('toggleRefreshIconCollapsed');
        var mt = document.getElementById('toggleRefreshMobileText');
        var mobileIcon = document.getElementById('toggleRefreshMobileIcon');

        if (dt) {
            dt.textContent = text;
            dt.classList.remove('text-green-600', 'dark:text-green-400', 'text-zinc-700', 'dark:text-zinc-300');
            dt.classList.add(autoRefresh ? 'text-green-600' : 'text-zinc-700');
            if (autoRefresh) dt.classList.add('dark:text-green-400'); else dt.classList.add('dark:text-zinc-300');
        }
        if (icon) {
            icon.classList.remove('text-green-600', 'dark:text-green-400', 'text-zinc-700', 'dark:text-zinc-300');
            icon.classList.add(autoRefresh ? 'text-green-600' : 'text-zinc-700');
            if (autoRefresh) icon.classList.add('dark:text-green-400'); else icon.classList.add('dark:text-zinc-300');
        }
        if (collapsedIcon) {
            collapsedIcon.classList.remove('text-green-600', 'dark:text-green-400', 'text-zinc-700', 'dark:text-zinc-300');
            collapsedIcon.classList.add(autoRefresh ? 'text-green-600' : 'text-zinc-700');
            if (autoRefresh) collapsedIcon.classList.add('dark:text-green-400'); else collapsedIcon.classList.add('dark:text-zinc-300');
        }
        if (mt) {
            mt.textContent = mobileText;
            mt.classList.remove('text-green-600', 'dark:text-green-400', 'text-zinc-500', 'dark:text-zinc-400');
            mt.classList.add(autoRefresh ? 'text-green-600' : 'text-zinc-500');
            if (autoRefresh) mt.classList.add('dark:text-green-400'); else mt.classList.add('dark:text-zinc-400');
        }
        if (mobileIcon) {
            mobileIcon.classList.remove('text-green-600', 'dark:text-green-400', 'text-zinc-500', 'dark:text-zinc-400');
            mobileIcon.classList.add(autoRefresh ? 'text-green-600' : 'text-zinc-500');
            if (autoRefresh) mobileIcon.classList.add('dark:text-green-400'); else mobileIcon.classList.add('dark:text-zinc-400');
        }
    }

    function startAutoRefresh() {
        stopAutoRefresh();
        refreshInterval = setInterval(loadResults, 60000);
    }

    function stopAutoRefresh() {
        if (refreshInterval) { clearInterval(refreshInterval); refreshInterval = null; }
    }

    function toggleRefreshState() {
        autoRefresh = !autoRefresh;
        syncToggleText();
        if (autoRefresh) {
            loadResults();
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    }

    // ===== SCROLL FAB =====
    function updateScrollTopFab() {
        if (!scrollTopFab || !mainScrollContainer) return;
        if (mainScrollContainer.scrollTop > 300) {
            scrollTopFab.classList.remove('opacity-0', 'pointer-events-none');
            scrollTopFab.classList.add('opacity-100');
        } else {
            scrollTopFab.classList.add('opacity-0', 'pointer-events-none');
            scrollTopFab.classList.remove('opacity-100');
        }
    }

    // ===== DELETE =====
    function doDeleteElectionData() {
        if (typeof Swal === 'undefined') {
            if (!window.confirm('DELETE ALL ELECTION DATA? This is irreversible. Type OK to continue.')) return;
            if (!window.confirm('Are you absolutely sure?')) return;
            var phrase = window.prompt('Type DELETE_ALL_ELECTION_DATA to confirm:');
            if (phrase !== 'DELETE_ALL_ELECTION_DATA') return;
            sendDelete(phrase);
            return;
        }

        Swal.fire({
            title: 'DELETE ALL ELECTION DATA?',
            text: 'This action is IRREVERSIBLE! All votes will be permanently deleted.',
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'Type DELETE_ALL_ELECTION_DATA to confirm',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Delete Everything',
            cancelButtonText: 'Cancel',
            inputValidator: function(value) {
                if (!value) return 'Please type the confirmation phrase!';
                if (value !== 'DELETE_ALL_ELECTION_DATA') return 'Incorrect phrase. Type exactly: DELETE_ALL_ELECTION_DATA';
            }
        }).then(function(result) {
            if (!result.isConfirmed) return;
            if (result.value !== 'DELETE_ALL_ELECTION_DATA') return;
            sendDelete(result.value);
        });
    }

    function sendDelete(phrase) {
        var fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fd.append('confirmation', phrase);

        fetch(appUrl + '/admin/api/results/delete', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                isFirstLoad = true;
                loadResults();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ text: d.message, icon: 'success', timer: 2000, showConfirmButton: false });
                } else {
                    alert(d.message);
                }
            } else {
                if (typeof Swal !== 'undefined') Swal.fire({ text: d.error, icon: 'error' });
                else alert(d.error);
            }
        })
        .catch(function() {
            if (typeof Swal !== 'undefined') Swal.fire({ text: 'Network error. Please try again.', icon: 'error' });
            else alert('Network error. Please try again.');
        });
    }

    // ===== APP INIT =====
    function startApp() {
        container = document.getElementById('resultsContainer');
        mobileMenuBtn = document.getElementById('mobileMenuBtn');
        mobileMenu = document.getElementById('mobileMenu');
        menuIconOpen = document.getElementById('menuIconOpen');
        menuIconClose = document.getElementById('menuIconClose');

        exportProgressModal = document.getElementById('exportProgressModal');
        exportProgressBar = document.getElementById('exportProgressBar');
        exportProgressText = document.getElementById('exportProgressText');
        exportProgressPercent = document.getElementById('exportProgressPercent');
        closeExportProgressBtn = document.getElementById('closeExportProgressBtn');

        toggleResultsPrivacy = document.getElementById('toggleResultsPrivacy');
        toggleResultsPrivacyLabel = document.getElementById('toggleResultsPrivacyLabel');
        toggleResultsPrivacyIcon = document.getElementById('toggleResultsPrivacyIcon');
        toggleResultsPrivacyCollapsed = document.getElementById('toggleResultsPrivacyCollapsed');
        toggleResultsPrivacyIconCollapsed = document.getElementById('toggleResultsPrivacyIconCollapsed');
        toggleResultsPrivacyMobile = document.getElementById('toggleResultsPrivacyMobile');
        toggleResultsPrivacyMobileText = document.getElementById('toggleResultsPrivacyMobileText');
        toggleResultsPrivacyMobileIcon = document.getElementById('toggleResultsPrivacyMobileIcon');

        openVoteLookupBtn = document.getElementById('openVoteLookupBtn');
        openVoteLookupBtnMobile = document.getElementById('openVoteLookupBtnMobile');
        openVoteLookupBtnCollapsed = document.getElementById('openVoteLookupBtnCollapsed');
        voteLookupModal = document.getElementById('voteLookupModal');
        voteLookupBackdrop = document.getElementById('voteLookupBackdrop');
        closeVoteLookupBtn = document.getElementById('closeVoteLookupBtn');
        voteLookupContent = document.getElementById('voteLookupContent');
        voteLookupSearchInput = document.getElementById('voteLookupSearchInput');
        voteLookupResults = document.getElementById('voteLookupResults');
        voteLookupBackBtn = document.getElementById('voteLookupBackBtn');
        voteLookupModalTitle = document.getElementById('voteLookupModalTitle');

        scrollTopFab = document.getElementById('scrollTopFab');
        mainScrollContainer = document.querySelector('main');

        if (!window.SSC) {
            console.error('window.SSC missing — results page cannot initialize');
            return;
        }
        appUrl = window.SSC.appUrl;
        csrfToken = window.SSC.csrfToken;
        candidateImagesUrl = window.SSC.candidateImagesUrl;

        if (!container) {
            console.error('#resultsContainer not found in DOM');
            return;
        }

        // --- Mobile menu wiring ---
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (mobileMenu.classList.contains('hidden')) openMobileMenu();
                else closeMobileMenu();
            });
            mobileMenu.querySelectorAll('a, button').forEach(function(item) {
                item.addEventListener('click', closeMobileMenu);
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

        // --- Privacy listeners ---
        if (toggleResultsPrivacy) toggleResultsPrivacy.addEventListener('click', togglePrivacy);
        if (toggleResultsPrivacyCollapsed) toggleResultsPrivacyCollapsed.addEventListener('click', togglePrivacy);
        if (toggleResultsPrivacyMobile) toggleResultsPrivacyMobile.addEventListener('click', togglePrivacy);

        // --- Vote lookup listeners ---
        if (openVoteLookupBtn) openVoteLookupBtn.addEventListener('click', openVoteLookupModal);
        if (openVoteLookupBtnCollapsed) openVoteLookupBtnCollapsed.addEventListener('click', openVoteLookupModal);
        if (openVoteLookupBtnMobile) openVoteLookupBtnMobile.addEventListener('click', openVoteLookupModal);
        if (closeVoteLookupBtn) closeVoteLookupBtn.addEventListener('click', closeVoteLookupModal);
        if (voteLookupBackdrop) voteLookupBackdrop.addEventListener('click', closeVoteLookupModal);
        if (voteLookupBackBtn) voteLookupBackBtn.addEventListener('click', goBackToLookupResults);
        if (voteLookupSearchInput) {
            voteLookupSearchInput.addEventListener('input', function() {
                clearTimeout(lookupSearchTimeout);
                var query = this.value.trim();
                if (query.length < 2) {
                    if (voteLookupResults) voteLookupResults.innerHTML = '<p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-8">Type a name or email to search</p>';
                    if (voteLookupBackBtn) voteLookupBackBtn.classList.add('hidden');
                    if (voteLookupModalTitle) voteLookupModalTitle.textContent = 'Vote Lookup';
                    lookupPreviousView = 'results';
                    return;
                }
                lookupSearchTimeout = setTimeout(function() { showLookupResults(query); }, 300);
            });
        }

        // --- Toggle refresh ---
        var desktopToggle = document.getElementById('toggleRefresh');
        if (desktopToggle) desktopToggle.addEventListener('click', toggleRefreshState);
        var mobileToggle = document.getElementById('toggleRefreshMobile');
        if (mobileToggle) mobileToggle.addEventListener('click', toggleRefreshState);
        toggleRefreshCollapsed = document.getElementById('toggleRefreshCollapsed');
        if (toggleRefreshCollapsed) toggleRefreshCollapsed.addEventListener('click', toggleRefreshState);

        // --- Delete buttons ---
        var deleteDesktop = document.getElementById('deleteElectionData');
        if (deleteDesktop) deleteDesktop.addEventListener('click', doDeleteElectionData);
        var deleteMobile = document.getElementById('deleteElectionDataMobile');
        if (deleteMobile) deleteMobile.addEventListener('click', doDeleteElectionData);
        deleteElectionDataCollapsed = document.getElementById('deleteElectionDataCollapsed');
        if (deleteElectionDataCollapsed) deleteElectionDataCollapsed.addEventListener('click', doDeleteElectionData);

        // --- Export buttons ---
        var exportDesktopBtn = document.getElementById('exportCSVBtn');
        if (exportDesktopBtn) exportDesktopBtn.addEventListener('click', function(e) { e.preventDefault(); startExport(); });
        var exportMobileBtn = document.getElementById('exportCSVBtnMobile');
        if (exportMobileBtn) exportMobileBtn.addEventListener('click', function(e) { e.preventDefault(); startExport(); });
        exportCSVBtnCollapsed = document.getElementById('exportCSVBtnCollapsed');
        if (exportCSVBtnCollapsed) exportCSVBtnCollapsed.addEventListener('click', function(e) { e.preventDefault(); startExport(); });

        if (closeExportProgressBtn) closeExportProgressBtn.addEventListener('click', hideExportModal);

        // --- Image error handler ---
        container.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG') {
                var p = e.target.parentElement;
                if (p) p.style.display = 'none';
            }
        }, true);

        // --- Scroll FAB ---
        if (mainScrollContainer) mainScrollContainer.addEventListener('scroll', updateScrollTopFab, { passive: true });
        if (scrollTopFab) {
            scrollTopFab.addEventListener('click', function() {
                if (mainScrollContainer) mainScrollContainer.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        // --- Cleanup ---
        window.addEventListener('beforeunload', function() {
            stopAutoRefresh();
            if (exportPollInterval) { clearInterval(exportPollInterval); exportPollInterval = null; }
            if (lookupSearchTimeout) { clearTimeout(lookupSearchTimeout); lookupSearchTimeout = null; }
            destroyAllCharts();
        });

        // --- Kick off ---
        syncToggleText();
        syncPrivacyToggleText();
        loadResults();
        startAutoRefresh();
    }

    // ===== DOM-READY BOOTSTRAP =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startApp);
    } else {
        startApp();
    }

})();