(function() {
    var csrfToken = window.SSC.csrfToken;
    var appUrl = window.SSC.appUrl;
    var selections = window.SSC.initialSelections || {};
    var initialSelectionsComplete = Object.keys(window.SSC.initialSelections || {}).length === 12;
    var isTestVote = window.SSC.isTestVote === true;
    var stickyBtn = null;
    var positionsContainer = null;

    // Section picker: tapping the box opens a modal with a search box and a list.
    // The chosen section's ID goes into a hidden field.
    function setupSectionPickerOld() {
        var input = document.getElementById('sectionInput');
        var hidden = document.getElementById('sectionSelect');
        var list = document.getElementById('sectionList');
        var warning = document.getElementById('sectionWarning');
        var toggle = document.getElementById('sectionToggle');
        var modal = document.getElementById('sectionModal');
        var backdrop = document.getElementById('sectionModalBackdrop');
        var closeBtn = document.getElementById('sectionModalOk');
        var search = document.getElementById('sectionModalSearch');
        var modalList = document.getElementById('sectionModalList');
        var modalEmpty = document.getElementById('sectionModalEmpty');
        var ghostTyped = document.getElementById('sectionModalGhostTyped');
        var ghostRest = document.getElementById('sectionModalGhostRest');
        if (!input || !hidden || !list || !modal || !search || !modalList) return;
        var options = [];
        for (var i = 0; i < list.options.length; i++) {
            options.push({ name: list.options[i].value, id: list.options[i].getAttribute('data-id') || '' });
        }
        function pick(name) {
            var found = null;
            for (var i = 0; i < options.length; i++) {
                if (options[i].name === name) { found = options[i]; break; }
            }
            input.value = found ? found.name : '';
            hidden.value = found ? found.id : '';
            if (warning) warning.classList.add('hidden');
            closeModal();
        }
        function renderList(filter) {
            var typed = filter || '';
            var low = typed.trim().toLowerCase();
            modalList.textContent = '';
            var count = 0;
            var firstMatch = '';
            for (var i = 0; i < options.length; i++) {
                var name = options[i].name;
                if (low !== '' && name.toLowerCase().indexOf(low) === -1) continue;
                if (firstMatch === '') firstMatch = name;
                count++;
                var item = document.createElement('button');
                item.type = 'button';
                item.setAttribute('role', 'option');
                item.setAttribute('data-value', name);
                item.className = 'block w-full text-left rounded-xl px-4 py-3 text-sm sm:text-base text-zinc-800 dark:text-zinc-100 hover:bg-blue-50 dark:hover:bg-zinc-800 focus:bg-blue-50 dark:focus:bg-zinc-800 focus:outline-none';
                item.textContent = name;
                modalList.appendChild(item);
            }
            if (modalEmpty) modalEmpty.classList.toggle('hidden', count > 0);
            // Grey hint: the first match, shown after what the user typed.
            var hint = (firstMatch !== '' && low !== '' && firstMatch.toLowerCase().indexOf(low) === 0 && firstMatch.length > typed.length) ? firstMatch.slice(typed.length) : '';
            if (ghostTyped) ghostTyped.textContent = typed;
            if (ghostRest) ghostRest.textContent = hint;
        }
        function openModal() {
            search.value = '';
            renderList('');
            modal.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            setTimeout(function() { search.focus(); }, 50);
        }
        function closeModal() {
            modal.classList.add('hidden');
            input.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
        input.addEventListener('click', openModal);
        input.addEventListener('focus', function() { input.blur(); openModal(); });
        if (toggle) toggle.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);
        modalList.addEventListener('click', function(e) {
            var item = e.target.closest('[data-value]');
            if (item) pick(item.getAttribute('data-value'));
        });
        search.addEventListener('input', function() { renderList(search.value); });
        search.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            } else if ((e.key === 'Tab' || e.key === 'ArrowRight') && ghostRest && ghostRest.textContent) {
                e.preventDefault();
                search.value = search.value + ghostRest.textContent;
                renderList(search.value);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                var first = modalList.querySelector('[data-value]');
                if (first) pick(first.getAttribute('data-value'));
            }
        });
        window.validateSectionPick = function() {
            if (hidden.value) return true;
            if (warning) warning.classList.remove('hidden');
            openModal();
            return false;
        };
    }
    /* Old dropdown picker (no longer used)
    function __unusedOldPicker() {
        function updateGhost() {
            var typed = input.value;
            var match = '';
            if (typed.trim() !== '') {
                var low = typed.toLowerCase();
                for (var i = 0; i < names.length; i++) {
                    if (names[i].toLowerCase().indexOf(low) === 0) { match = names[i]; break; }
                }
            }
            if (ghostTyped) ghostTyped.textContent = typed;
            if (ghostRest) ghostRest.textContent = match && match.length > typed.length ? match.slice(typed.length) : '';
        }
        function syncId() {
            var opt = findExact(input.value);
            hidden.value = opt ? (opt.getAttribute('data-id') || '') : '';
            if (warning) warning.classList.add('hidden');
            updateGhost();
        }
        var menu = document.getElementById('sectionMenu');
        var menuEmpty = document.getElementById('sectionMenuEmpty');
        var toggle = document.getElementById('sectionToggle');
        function renderMenu(filter) {
            if (!menu) return;
            var low = (filter || '').trim().toLowerCase();
            menu.textContent = '';
            var count = 0;
            for (var i = 0; i < list.options.length; i++) {
                var opt = list.options[i];
                if (low !== '' && opt.value.toLowerCase().indexOf(low) === -1) continue;
                count++;
                var item = document.createElement('button');
                item.type = 'button';
                item.setAttribute('role', 'option');
                item.setAttribute('data-value', opt.value);
                item.className = 'block w-full text-left px-4 py-2 text-sm sm:text-base text-zinc-800 dark:text-zinc-100 hover:bg-blue-50 dark:hover:bg-zinc-800 focus:bg-blue-50 dark:focus:bg-zinc-800 focus:outline-none';
                item.textContent = opt.value;
                menu.appendChild(item);
            }
            if (menuEmpty) menuEmpty.classList.toggle('hidden', count > 0);
        }
        function openMenu() {
            if (!menu) return;
            renderMenu(input.value);
            menu.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
        }
        function closeMenu() {
            if (menu) menu.classList.add('hidden');
            if (menuEmpty) menuEmpty.classList.add('hidden');
            input.setAttribute('aria-expanded', 'false');
        }
        function choose(value) {
            input.value = value;
            closeMenu();
            syncId();
        }
        if (menu) {
            menu.addEventListener('mousedown', function(e) {
                var item = e.target.closest('[data-value]');
                if (!item) return;
                e.preventDefault();
                choose(item.getAttribute('data-value'));
            });
        }
        if (toggle) {
            toggle.addEventListener('click', function() {
                if (menu && !menu.classList.contains('hidden')) closeMenu();
                else { input.focus(); openMenu(); }
            });
        }
        input.addEventListener('focus', openMenu);
        input.addEventListener('input', function() { openMenu(); syncId(); });
        input.addEventListener('change', syncId);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMenu();
        });
        document.addEventListener('mousedown', function(e) {
            if (!e.target.closest('#sectionInput') && !e.target.closest('#sectionMenu') && !e.target.closest('#sectionToggle')) closeMenu();
        });
        input.addEventListener('keydown', function(e) {
            if ((e.key === 'Tab' || e.key === 'ArrowRight') && ghostRest && ghostRest.textContent) {
                e.preventDefault();
                input.value = input.value + ghostRest.textContent;
                syncId();
            }
        });
        input.addEventListener('blur', function() {
            setTimeout(closeMenu, 150);
            if (input.value.trim() !== '' && !findExact(input.value) && warning) {
                warning.classList.remove('hidden');
            }
        });
        window.validateSectionPick = function() {
            if (findExact(input.value)) return true;
            if (warning) warning.classList.remove('hidden');
            input.focus();
            return false;
        };
    */
    setupSectionPickerOld();
    // Unused SweetAlert2 picker, kept switched off.
    function unusedSwalPicker() {
        var input = document.getElementById('sectionInput');
        var hidden = document.getElementById('sectionSelect');
        var list = document.getElementById('sectionList');
        var warning = document.getElementById('sectionWarning');
        var toggle = document.getElementById('sectionToggle');
        if (!input || !hidden || !list) return;
        var options = [];
        for (var i = 0; i < list.options.length; i++) {
            options.push({ name: list.options[i].value, id: list.options[i].getAttribute('data-id') || '' });
        }
        function choose(opt) {
            input.value = opt.name;
            hidden.value = opt.id;
            if (warning) warning.classList.add('hidden');
        }
        function openPicker() {
            if (typeof Swal === 'undefined') return;
            if (Swal.isVisible()) return;
            Swal.fire({
                title: 'Select your Section',
                html: '<div style="position:relative;text-align:left">' +
                    '<div id="swalSectionGhost" aria-hidden="true" style="position:absolute;inset:0;padding:.6rem .9rem;border:1px solid transparent;border-radius:.75rem;white-space:pre;overflow:hidden;pointer-events:none;box-sizing:border-box"><span id="swalGhostTyped" style="color:transparent"></span><span id="swalGhostRest" style="color:#a1a1aa"></span></div>' +
                    '<input id="swalSectionSearch" type="search" autocomplete="off" placeholder="Type to search (e.g. IT 101)" style="position:relative;width:100%;padding:.6rem .9rem;border:1px solid #d4d4d8;border-radius:.75rem;background:transparent;box-sizing:border-box;font-size:1rem">' +
                    '</div>' +
                    '<div id="swalSectionList" role="listbox" style="margin-top:.75rem;max-height:50vh;overflow-y:auto;text-align:left"></div>' +
                    '<p id="swalSectionEmpty" style="display:none;margin-top:.5rem;font-size:.875rem;color:#71717a;text-align:left">No section found</p>',
                showConfirmButton: false,
                showCloseButton: true,
                focusConfirm: false,
                width: 420,
                didOpen: function() {
                    var popup = Swal.getPopup();
                    var search = popup.querySelector('#swalSectionSearch');
                    var modalList = popup.querySelector('#swalSectionList');
                    var empty = popup.querySelector('#swalSectionEmpty');
                    var ghostTyped = popup.querySelector('#swalGhostTyped');
                    var ghostRest = popup.querySelector('#swalGhostRest');
                    var firstMatch = null;
                    function render() {
                        var typed = search.value;
                        var low = typed.trim().toLowerCase();
                        modalList.textContent = '';
                        firstMatch = null;
                        for (var j = 0; j < options.length; j++) {
                            var opt = options[j];
                            if (low !== '' && opt.name.toLowerCase().indexOf(low) === -1) continue;
                            if (!firstMatch) firstMatch = opt;
                            var item = document.createElement('button');
                            item.type = 'button';
                            item.setAttribute('role', 'option');
                            item.style.cssText = 'display:block;width:100%;text-align:left;padding:.75rem 1rem;border-radius:.75rem;background:none;border:0;cursor:pointer;font-size:1rem;color:#27272a';
                            item.textContent = opt.name;
                            item.addEventListener('click', function(opt) {
                                return function() { choose(opt); Swal.close(); };
                            }(opt));
                            modalList.appendChild(item);
                        }
                        empty.style.display = firstMatch ? 'none' : 'block';
                        // Grey hint: the rest of the first match.
                        var hint = (firstMatch && low !== '' && firstMatch.name.toLowerCase().indexOf(low) === 0 && firstMatch.name.length > typed.length) ? firstMatch.name.slice(typed.length) : '';
                        ghostTyped.textContent = typed;
                        ghostRest.textContent = hint;
                    }
                    search.addEventListener('input', render);
                    search.addEventListener('keydown', function(e) {
                        if ((e.key === 'Tab' || e.key === 'ArrowRight') && ghostRest.textContent) {
                            e.preventDefault();
                            search.value = search.value + ghostRest.textContent;
                            render();
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            if (firstMatch) { choose(firstMatch); Swal.close(); }
                        }
                    });
                    render();
                    search.focus();
                }
            });
        }
        input.addEventListener('click', openPicker);
        input.addEventListener('focus', function() { input.blur(); openPicker(); });
        if (toggle) toggle.addEventListener('click', openPicker);
    }

    var totalPositions = 0;
    var positionOrder = [];
    var positionNames = {};
    var reviewBtn = null;
    var submitBtn = null;
    var confirmCheckbox = null;
    var confirmCountdown = null;
    var confirmWarning = null;
    var countdownTimer = null;
    var countdownSeconds = 3;
    var viewSelectionsBtn = null;
    var turnstileVerified = false;
    var hasCompletedOnce = false;
    
    var selectionChip = null;
    var selectionChipText = null;
    
    var viewSelectionsModal = null;
    var viewSelectionsBackdrop = null;
    var viewSelectionsContent = null;
    var closeViewSelectionsBtn = null;
    var modalEditSelectionsBtn = null;
    var modalOkBtn = null;
    
    function showStep(step) {
        var gate = document.getElementById('turnstileGate');
        var s1 = document.getElementById('step1');
        var s2 = document.getElementById('step2');
        if (gate) gate.classList.add('hidden');
        if (s1) s1.classList.add('hidden');
        if (s2) s2.classList.add('hidden');
        var target = document.getElementById('step' + step);
        if (target) target.classList.remove('hidden');
        
        if (step === 1) {
            if (confirmCheckbox) confirmCheckbox.checked = false;
            if (confirmCountdown) confirmCountdown.classList.add('hidden');
            if (confirmWarning) confirmWarning.classList.remove('hidden');
            if (countdownTimer) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }
            if (submitBtn) {
                submitBtn.classList.add('hidden', 'opacity-0', 'translate-x-4');
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        if (step === 2) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function proceedToVoting(delay) {
        if (turnstileVerified) return;

        turnstileVerified = true;

        setTimeout(function() {
            showStep(1);
            loadElectionData();
        }, delay || 0);
    }

    window.proceedToVoting = proceedToVoting;

    function loadElectionData() {
        var container = document.getElementById('positionsContainer');
        if (!container) return;
        container.textContent = '';

        var loadingDiv = document.createElement('div');
        loadingDiv.className = 'text-center py-8 text-zinc-500';
        loadingDiv.textContent = 'Loading candidates...';
        container.appendChild(loadingDiv);

        fetch(appUrl + '/vote/election-data', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            buildCandidateList(data.data);
        })
        .catch(function() {
            if (container) {
                container.textContent = '';
                var errorDiv = document.createElement('div');
                errorDiv.className = 'alert-error';
                errorDiv.textContent = 'Failed to load candidates. Please refresh the page.';
                container.appendChild(errorDiv);
            }
        });
    }

    window.onTurnstileSuccess = function(token) {
        if (window.SSC && window.SSC.isOffline === true) {
            return;
        }

        proceedToVoting(2000);
    };

    if (window.SSC && window.SSC.isOffline === true) {
        proceedToVoting(0);
    }

    function buildCandidateList(electionData) {
        var container = document.getElementById('positionsContainer');
        if (!container) return;
        container.textContent = '';
        
        totalPositions = electionData.length;
        positionOrder = [];
        positionNames = {};
        var fragment = document.createDocumentFragment();
        
        electionData.forEach(function(position, index) {
            positionOrder.push(position.id);
            positionNames[position.id] = position.name;
            
            var sectionDiv = document.createElement('div');
            sectionDiv.className = 'mb-8 sm:mb-10';
            sectionDiv.setAttribute('data-position-section', position.id);
            
            var sectionHeader = document.createElement('div');
            sectionHeader.className = 'mb-4 px-1 flex items-center gap-3';
            var sectionTitle = document.createElement('h3');
            sectionTitle.className = 'text-xl sm:text-2xl lg:text-3xl font-black tracking-tight text-zinc-900 dark:text-white';
            sectionTitle.textContent = position.name;
            sectionHeader.appendChild(sectionTitle);
            
            var headerCheckSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            headerCheckSvg.setAttribute('class', 'w-7 h-7 sm:w-8 sm:h-8 text-green-500 shrink-0 hidden');
            headerCheckSvg.setAttribute('fill', 'none');
            headerCheckSvg.setAttribute('stroke', 'currentColor');
            headerCheckSvg.setAttribute('viewBox', '0 0 24 24');
            var headerCheckPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            headerCheckPath.setAttribute('stroke-linecap', 'round');
            headerCheckPath.setAttribute('stroke-linejoin', 'round');
            headerCheckPath.setAttribute('stroke-width', '2.5');
            headerCheckPath.setAttribute('d', 'M5 13l4 4L19 7');
            headerCheckSvg.appendChild(headerCheckPath);
            headerCheckSvg.setAttribute('data-header-check', position.id);
            sectionHeader.appendChild(headerCheckSvg);
            
            sectionDiv.appendChild(sectionHeader);
            
            var candidatesDiv = document.createElement('div');
            candidatesDiv.className = 'grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6';
            
            position.candidates.forEach(function(candidate) {
                var card = document.createElement('button');
                card.type = 'button';
                card.className = 'relative flex flex-col items-center text-center p-4 sm:p-6 rounded-3xl border border-zinc-200 dark:border-zinc-800 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-all duration-300 select-none touch-manipulation group';
                card.setAttribute('data-position-id', position.id);
                card.setAttribute('data-candidate-id', candidate.id);
                card.setAttribute('data-candidate-name', candidate.name);
                card.setAttribute('data-partylist-name', candidate.partylist_name || '');
                card.setAttribute('data-partylist-badge-color', candidate.badge_color || '#1e40af');
                card.setAttribute('data-partylist-badge-text-color', candidate.badge_text_color || '#ffffff');
                card.setAttribute('data-image-path', candidate.image_path || '');
                
                if (!selections[position.id] && candidate.is_default_winner == 1) {
                    selections[position.id] = {
                        candidate_id: String(candidate.id),
                        candidate_name: candidate.name,
                        partylist_name: candidate.partylist_name || '',
                        partylist_badge_color: candidate.badge_color || '#1e40af',
                        partylist_badge_text_color: candidate.badge_text_color || '#ffffff',
                        image_path: candidate.image_path || ''
                    };
                }
                var positionHasDefault = position.candidates.some(function(c) { return c.is_default_winner == 1; });
                if (positionHasDefault) {
                    card.setAttribute('data-locked', 'true');
                    card.disabled = true;
                    card.classList.add('cursor-not-allowed', 'opacity-60');
                    card.classList.remove('cursor-pointer', 'hover:bg-zinc-50', 'dark:hover:bg-zinc-800/50');
                }
                if (selections[position.id] && selections[position.id].candidate_id === String(candidate.id)) {
                    card.classList.add('ring-4', 'ring-blue-500', 'bg-blue-50/50', 'dark:bg-blue-950/30');
                }
                
                card.addEventListener('click', function() {
                    if (positionHasDefault) return;
                    selections[position.id] = {
                        candidate_id: String(candidate.id),
                        candidate_name: candidate.name,
                        partylist_name: candidate.partylist_name || '',
                        partylist_badge_color: candidate.badge_color || '#1e40af',
                        partylist_badge_text_color: candidate.badge_text_color || '#ffffff',
                        image_path: candidate.image_path || ''
                    };
                    
                    var siblings = candidatesDiv.querySelectorAll('[data-position-id="' + position.id + '"]');
                    for (var s = 0; s < siblings.length; s++) {
                        siblings[s].classList.remove('ring-4', 'ring-blue-500', 'bg-blue-50/50', 'dark:bg-blue-950/30');
                    }
                    card.classList.add('ring-4', 'ring-blue-500', 'bg-blue-50/50', 'dark:bg-blue-950/30');
                    
                    checkAllSelected();
                });
                
                var imgWrap = document.createElement('div');
                imgWrap.className = 'relative z-10 w-40 h-40 sm:w-56 sm:h-56 lg:w-72 lg:h-72 mb-4';
                
                if (candidate.image_path) {
                    var img = document.createElement('img');
                    img.src = window.SSC.candidateImagesUrl + '/' + candidate.image_path;
                    img.alt = candidate.name;
                    img.className = 'w-full h-full object-contain';
                    img.setAttribute('loading', 'lazy');
                    imgWrap.appendChild(img);
                } else {
                    imgWrap.classList.add('flex', 'items-center', 'justify-center');
                    var svgNS = 'http://www.w3.org/2000/svg';
                    var svg = document.createElementNS(svgNS, 'svg');
                    svg.setAttribute('class', 'w-16 h-16 sm:w-20 sm:h-20 lg:w-24 lg:h-24 text-zinc-300 dark:text-zinc-600');
                    svg.setAttribute('fill', 'currentColor');
                    svg.setAttribute('viewBox', '0 0 24 24');
                    var path = document.createElementNS(svgNS, 'path');
                    path.setAttribute('d', 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z');
                    svg.appendChild(path);
                    imgWrap.appendChild(svg);
                }
                card.appendChild(imgWrap);

                if (candidate.is_default_winner == 1) {
                    var winnerBadge = document.createElement('span');
                    winnerBadge.className = 'pointer-events-none absolute top-2 left-0 right-0 z-20 mx-auto w-max px-5 py-1.5 rounded-md bg-amber-400 text-amber-950 text-sm sm:text-base font-black uppercase tracking-widest shadow-lg select-none';
                    winnerBadge.setAttribute('aria-hidden', 'true');
                    winnerBadge.textContent = 'WINNER';
                    card.appendChild(winnerBadge);
                }
                
                var nameSpan = document.createElement('span');
                nameSpan.className = 'relative z-10 font-bold text-zinc-900 dark:text-white text-base sm:text-lg lg:text-xl';
                nameSpan.textContent = candidate.name;
                card.appendChild(nameSpan);
                
                if (candidate.partylist_name) {
                    var badge = document.createElement('span');
                    badge.className = 'relative z-10 badge-blue text-[11px] sm:text-xs mt-2 px-3 py-1';
                    badge.style.backgroundColor = candidate.badge_color || '#1e40af';
                    badge.style.color = candidate.badge_text_color || '#ffffff';
                    badge.style.border = '1px solid #ccc';
                    badge.style.borderRadius = '8px';
                    badge.textContent = candidate.partylist_name;
                    card.appendChild(badge);
                }
                
                if (candidate.platform) {
                    var platformP = document.createElement('p');
                    platformP.className = 'mt-2 text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2';
                    platformP.textContent = candidate.platform;
                    card.appendChild(platformP);
                }
                
                candidatesDiv.appendChild(card);
            });
            
            sectionDiv.appendChild(candidatesDiv);
            fragment.appendChild(sectionDiv);
        });
        
        container.appendChild(fragment);
        
        positionsContainer = document.getElementById('positionsContainer');
        stickyBtn = document.getElementById('stickyReviewBtn');
        selectionChip = document.getElementById('selectionChip');
        selectionChipText = document.getElementById('selectionChipText');
        
        if (initialSelectionsComplete) {
            if (stickyBtn) {
                stickyBtn.classList.remove('hidden');
            }
            hasCompletedOnce = true;
        }
        
        checkAllSelected();
    }
    
    function collectSelections() {
        var clean = {};
        for (var posId in selections) {
            if (selections[posId] && selections[posId].candidate_id) {
                clean[posId] = selections[posId];
            }
        }
        selections = clean;
        return selections;
    }
    
    function updateSelectionChip() {
        var selectedCount = Object.keys(selections).length;
        
        if (selectionChipText) {
            selectionChipText.textContent = selectedCount + '/' + totalPositions + ' Selected';
        }
        
        if (selectedCount > 0 && selectedCount < totalPositions && selectionChip) {
            selectionChip.classList.remove('hidden');
            selectionChip.offsetHeight;
            selectionChip.classList.remove('opacity-0');
        } else if (selectedCount >= totalPositions && selectionChip) {
            selectionChip.classList.add('opacity-0');
            setTimeout(function() {
                selectionChip.classList.add('hidden');
            }, 300);
        }
    }
    
    reviewBtn = document.getElementById('reviewSelectionsBtn');
    if (reviewBtn) {
        reviewBtn.addEventListener('click', function() {
            collectSelections();
            var allSelected = Object.keys(selections).length >= totalPositions;
            if (!allSelected) {
                alert('Please select a candidate for each position.');
                return;
            }
            showStep(2);
        });
    }
    
    viewSelectionsModal = document.getElementById('viewSelectionsModal');
    viewSelectionsBackdrop = document.getElementById('viewSelectionsBackdrop');
    viewSelectionsContent = document.getElementById('viewSelectionsContent');
    closeViewSelectionsBtn = document.getElementById('closeViewSelectionsBtn');
    modalEditSelectionsBtn = document.getElementById('modalEditSelectionsBtn');
    modalOkBtn = document.getElementById('modalOkBtn');
    viewSelectionsBtn = document.getElementById('viewSelectionsBtn');
    
    function openViewSelectionsModal() {
        if (!viewSelectionsModal || !viewSelectionsContent) return;
        
        function esc(s) {
            return String(s).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        var html = '';
        for (var j = 0; j < positionOrder.length; j++) {
            var positionId = positionOrder[j];
            var positionName = positionNames[positionId] || 'Position ' + positionId;
            var sel = selections[positionId];
            
            html += '<div class="flex flex-col items-center text-center p-3 sm:p-5 rounded-2xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/30 hover:shadow-lg hover:shadow-zinc-200/50 dark:hover:shadow-zinc-900/50 transition-all duration-300">';
            html += '<p class="text-sm sm:text-lg font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider mb-3">' + positionName + '</p>';
            if (sel && sel.image_path) {
                html += '<img src="' + window.SSC.candidateImagesUrl + '/' + sel.image_path + '" class="w-36 h-36 sm:w-48 sm:h-48 lg:w-56 lg:h-56 rounded-2xl object-contain shadow-lg shadow-zinc-300/50 dark:shadow-zinc-900/50 mb-3" alt="">';
            } else {
                html += '<div class="w-36 h-36 sm:w-48 sm:h-48 lg:w-56 lg:h-56 rounded-2xl bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mb-3"><svg class="w-14 h-14 sm:w-16 sm:h-16 lg:w-20 lg:h-20 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>';
            }
            if (sel) {
                html += '<p class="text-sm sm:text-lg font-semibold text-zinc-900 dark:text-white">' + sel.candidate_name + '</p>';
                if (sel.partylist_name) {
                    var plColor = sel.partylist_badge_color || '#1e40af';
                    var plText = sel.partylist_badge_text_color || '#ffffff';
                    html += '<span class="text-sm sm:text-lg mt-1.5 inline-block px-3 py-1" style="background-color:' + plColor + ';color:' + plText + ';border:1px solid #ccc;border-radius:8px;">' + sel.partylist_name + '</span>';
                }
            } else {
                html += '<p class="text-sm sm:text-lg text-red-500">No candidate selected</p>';
            }
            html += '</div>';
        }
        
        viewSelectionsContent.innerHTML = html;
        viewSelectionsModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeViewSelectionsModal() {
        if (viewSelectionsModal) {
            viewSelectionsModal.classList.add('hidden');
        }
        document.body.style.overflow = '';
    }
    
    if (viewSelectionsBtn) {
        viewSelectionsBtn.addEventListener('click', openViewSelectionsModal);
    }
    
    if (closeViewSelectionsBtn) {
        closeViewSelectionsBtn.addEventListener('click', closeViewSelectionsModal);
    }
    
    if (viewSelectionsBackdrop) {
        viewSelectionsBackdrop.addEventListener('click', closeViewSelectionsModal);
    }
    
    if (modalEditSelectionsBtn) {
        modalEditSelectionsBtn.addEventListener('click', function() {
            closeViewSelectionsModal();
            showStep(1);
        });
    }
    
    if (modalOkBtn) {
        modalOkBtn.addEventListener('click', closeViewSelectionsModal);
    }
    
    function checkAllSelected() {
        collectSelections();
        var allSelected = Object.keys(selections).length >= totalPositions;
        
        if (allSelected && !hasCompletedOnce) {
            hasCompletedOnce = true;
        }
        
        if (hasCompletedOnce || initialSelectionsComplete) {
            if (stickyBtn) stickyBtn.classList.remove('hidden');
        } else {
            if (stickyBtn) stickyBtn.classList.toggle('hidden', !allSelected);
        }
        
        updateHeaderChecks();
        updateSelectionChip();
    }
    
    function updateHeaderChecks() {
        var headerChecks = document.querySelectorAll('[data-header-check]');
        headerChecks.forEach(function(check) {
            var posId = check.getAttribute('data-header-check');
            if (selections[posId]) {
                check.classList.remove('hidden');
            } else {
                check.classList.add('hidden');
            }
        });
    }
    
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    confirmCheckbox = document.getElementById('confirmVoteCheckbox');
    confirmCountdown = document.getElementById('confirmCountdown');
    confirmWarning = document.getElementById('confirmWarning');
    submitBtn = document.getElementById('submitVoteBtn');

    if (confirmCheckbox && submitBtn && confirmCountdown && confirmWarning) {
        confirmCheckbox.addEventListener('change', function() {
            if (confirmCheckbox.checked) {
                countdownSeconds = 3;
                confirmCountdown.classList.remove('hidden');
                confirmCountdown.textContent = 'Please wait ' + countdownSeconds + '...';
                
                countdownTimer = setInterval(function() {
                    countdownSeconds--;
                    if (countdownSeconds <= 0) {
                        clearInterval(countdownTimer);
                        countdownTimer = null;
                        confirmCountdown.classList.add('hidden');
                        confirmWarning.classList.add('hidden');
                        submitBtn.classList.remove('hidden');
                        submitBtn.offsetHeight;
                        submitBtn.classList.remove('opacity-0', 'translate-x-4');
                    } else {
                        confirmCountdown.textContent = 'Please wait ' + countdownSeconds + '...';
                    }
                }, 1000);
            } else {
                if (countdownTimer) {
                    clearInterval(countdownTimer);
                    countdownTimer = null;
                }
                confirmCountdown.classList.add('hidden');
                confirmWarning.classList.remove('hidden');
                submitBtn.classList.add('opacity-0', 'translate-x-4');
                setTimeout(function() {
                    if (!confirmCheckbox.checked) {
                        submitBtn.classList.add('hidden');
                    }
                }, 200);
            }
        });
    }
    
    if (submitBtn) {
        submitBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            if (!confirmCheckbox || !confirmCheckbox.checked) {
                alert('Please confirm that you have reviewed your selections before submitting.');
                return;
            }
            
            var nameInput = document.getElementById('nameInput');
            var emailInput = document.getElementById('emailInput');
            var nameValue = '';
            var emailValue = '';
            var sectionValue = '';
            
            if (!isTestVote) {
                if (nameInput && !nameInput.checkValidity()) {
                    nameInput.reportValidity();
                    return;
                }
                
                if (emailInput && !emailInput.checkValidity()) {
                    emailInput.reportValidity();
                    return;
                }
                
                var sectionSelect = document.getElementById('sectionSelect');
                var sectionInput = document.getElementById('sectionInput');
                if (sectionSelect && !sectionSelect.value) {
                    if (window.validateSectionPick) window.validateSectionPick();
                    return;
                }
                
                nameValue = nameInput ? nameInput.value.trim() : '';
                emailValue = emailInput ? emailInput.value.trim() : '';
                sectionValue = sectionSelect ? sectionSelect.value : '';
            }
            
            collectSelections();
            
            var btn = document.getElementById('submitVoteBtn');
            if (btn) { btn.disabled = true; btn.textContent = 'Submitting...'; }
            
            var formData = new FormData();
            formData.append('csrf_token', csrfToken);
            if (!isTestVote) {
                formData.append('name', nameValue);
                formData.append('email', emailValue);
                formData.append('section_id', sectionValue);
            }
            for (var posId in selections) {
                formData.append('selections[' + posId + ']', selections[posId].candidate_id);
            }
            
            try {
                var response = await fetch(appUrl + '/vote/submit', {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.error || 'Failed to submit vote');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Submit Vote'; }
            }
        });
    }

    window.addEventListener('beforeunload', function() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        stickyBtn = null;
        positionsContainer = null;
        reviewBtn = null;
        submitBtn = null;
        confirmCheckbox = null;
        confirmCountdown = null;
        confirmWarning = null;
        selectionChip = null;
        selectionChipText = null;
        viewSelectionsBtn = null;
        viewSelectionsModal = null;
        viewSelectionsBackdrop = null;
        viewSelectionsContent = null;
        closeViewSelectionsBtn = null;
        modalEditSelectionsBtn = null;
        modalOkBtn = null;
        selections = null;
        positionOrder = null;
        positionNames = null;
        document.body.style.overflow = '';
    });
})();