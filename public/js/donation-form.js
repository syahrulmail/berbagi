(function () {
    'use strict';

    function initSearchableSelects() {
        document.querySelectorAll('[data-searchable-select]').forEach(function (root) {
            if (root.dataset.searchableInitialized === '1') return;
            root.dataset.searchableInitialized = '1';

            var input = root.querySelector('.searchable-select-input');
            var hidden = root.querySelector('input[type="hidden"]');
            var list = root.querySelector('.searchable-select-list');
            var items = Array.prototype.slice.call(list.querySelectorAll('li[data-value]'));
            var allowEmpty = root.getAttribute('data-allow-empty') === '1';
            var isOpen = false;
            var activeIndex = -1;

            function phoneDigits(s) {
                return (s || '').replace(/\D+/g, '');
            }

            function normalizePhone(s) {
                s = phoneDigits(s);
                if (s.indexOf('62') === 0) s = s.slice(2);
                if (s.charAt(0) === '0') s = s.slice(1);
                return s;
            }

            function visibleItems() {
                return items.filter(function (it) {
                    return !it.classList.contains('hidden-item');
                });
            }

            function setActive(idx) {
                items.forEach(function (it, i) {
                    it.classList.toggle('active', i === idx);
                });
                activeIndex = idx;
                if (idx >= 0 && items[idx]) {
                    items[idx].scrollIntoView({ block: 'nearest' });
                }
            }

            function applyFilter() {
                var query = input.value.trim();
                var qNorm = normalizePhone(query);
                items.forEach(function (it) {
                    var name = (it.dataset.search || '').toLowerCase();
                    var phoneNorm = normalizePhone(it.dataset.phone);
                    var match;
                    if (query === '') {
                        match = true;
                    } else if (name.indexOf(query.toLowerCase()) !== -1) {
                        match = true;
                    } else if (phoneNorm && qNorm && phoneNorm.indexOf(qNorm) !== -1) {
                        match = true;
                    } else {
                        match = false;
                    }
                    it.classList.toggle('hidden-item', !match || it.classList.contains('cat-hidden'));
                });
            }

            function open() {
                isOpen = true;
                root.classList.add('open');
                applyFilter();
                setActive(-1);
            }

            function close() {
                isOpen = false;
                root.classList.remove('open');
                setActive(-1);
            }

            function selectItem(item) {
                if (!item) return;
                hidden.value = item.getAttribute('data-value');
                input.value = item.textContent.trim();
                close();
                root.dispatchEvent(new CustomEvent('searchable-selected', { detail: { value: hidden.value } }));
            }

            function selectedItem() {
                if (!hidden.value) return null;
                for (var i = 0; i < items.length; i++) {
                    if (items[i].getAttribute('data-value') === hidden.value) return items[i];
                }
                return null;
            }

            input.addEventListener('focus', open);
            input.addEventListener('input', function () {
                open();
                applyFilter();
                setActive(-1);
                var sel = selectedItem();
                if (sel && input.value.trim() !== sel.textContent.trim()) {
                    hidden.value = '';
                }
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    close();
                    return;
                }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (!isOpen) {
                        open();
                        return;
                    }
                    var vis = visibleItems();
                    if (!vis.length) return;
                    var cur = items[activeIndex];
                    var idx = vis.indexOf(cur);
                    idx = (idx + (e.key === 'ArrowDown' ? 1 : -1) + vis.length) % vis.length;
                    setActive(items.indexOf(vis[idx]));
                    return;
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (activeIndex >= 0 && items[activeIndex] && !items[activeIndex].classList.contains('hidden-item')) {
                        selectItem(items[activeIndex]);
                        return;
                    }
                    var vis = visibleItems();
                    if (vis.length === 1) {
                        selectItem(vis[0]);
                        return;
                    }
                }
            });

            input.addEventListener('blur', function () {
                setTimeout(function () {
                    if (root.contains(document.activeElement)) return;
                    var sel = selectedItem();
                    var query = input.value.trim();
                    if (sel) {
                        if (sel.textContent.trim() !== query) {
                            input.value = sel.textContent.trim();
                        }
                    } else if (query !== '' && !allowEmpty) {
                        input.value = '';
                    }
                    close();
                }, 120);
            });

            list.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            list.addEventListener('click', function (e) {
                var item = e.target.closest ? e.target.closest('li[data-value]') : null;
                if (item) selectItem(item);
            });

            root.addEventListener('searchable-items-refresh', function () {
                items = Array.prototype.slice.call(list.querySelectorAll('li[data-value]'));
                applyFilter();
            });

            root.addEventListener('searchable-refresh', function () {
                applyFilter();
            });
        });
    }

    function initItemCategoryFilter() {
        document.querySelectorAll('[data-item-row]').forEach(function (row) {
            if (row.dataset.catInit === '1') return;
            row.dataset.catInit = '1';

            var catSelect = row.querySelector('.item-category');
            var progRoot = row.querySelector('[data-searchable-select]');
            if (!catSelect || !progRoot) return;

            var hidden = progRoot.querySelector('input[type="hidden"]');
            var input = progRoot.querySelector('.searchable-select-input');
            var items = Array.prototype.slice.call(progRoot.querySelectorAll('li[data-value][data-category]'));

            function apply() {
                var cat = catSelect.value;
                items.forEach(function (it) {
                    it.classList.toggle('cat-hidden', cat !== '' && it.getAttribute('data-category') !== cat);
                });
                progRoot.dispatchEvent(new CustomEvent('searchable-refresh'));
            }

            function syncCategory() {
                var sel = null;
                for (var i = 0; i < items.length; i++) {
                    if (items[i].getAttribute('data-value') === hidden.value) { sel = items[i]; break; }
                }
                if (sel && sel.getAttribute('data-category')) {
                    catSelect.value = sel.getAttribute('data-category');
                }
                apply();
            }

            catSelect.addEventListener('change', function () {
                if (hidden.value) {
                    var sel = null;
                    for (var i = 0; i < items.length; i++) {
                        if (items[i].getAttribute('data-value') === hidden.value) { sel = items[i]; break; }
                    }
                    if (sel && catSelect.value !== '' && sel.getAttribute('data-category') !== catSelect.value) {
                        hidden.value = '';
                        input.value = '';
                    }
                }
                apply();
            });

            progRoot.addEventListener('searchable-selected', syncCategory);
            syncCategory();
        });
    }

    function formatNumber(n) {
        return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function resetRow(row) {
        row.querySelectorAll('input, select').forEach(function (field) {
            field.value = '';
        });
    }

    function reindexRows() {
        var container = document.getElementById('donation-items');
        var rows = container.querySelectorAll('[data-item-row]');
        rows.forEach(function (row, i) {
            row.querySelectorAll('input, select').forEach(function (field) {
                var name = field.getAttribute('name') || '';
                name = name.replace(/items\[\d+\]/, 'items[' + i + ']');
                field.setAttribute('name', name);
            });
        });
    }

    function updateTotal() {
        var total = 0;
        document.querySelectorAll('#donation-items .item-amount').forEach(function (inp) {
            var v = parseFloat(inp.value);
            if (!isNaN(v) && v > 0) total += v;
        });
        var el = document.getElementById('donation-total');
        if (el) el.textContent = 'Rp ' + formatNumber(total);
    }

    function initItemRows() {
        var container = document.getElementById('donation-items');
        if (!container) return;
        if (container.dataset.rowsInit === '1') return;
        container.dataset.rowsInit = '1';

        var addBtn = document.getElementById('add-item-btn');

        container.addEventListener('click', function (e) {
            var rm = e.target.closest ? e.target.closest('.item-remove') : null;
            if (!rm) return;
            var rows = container.querySelectorAll('[data-item-row]');
            if (rows.length <= 1) {
                resetRow(rows[0]);
                return;
            }
            rm.closest('[data-item-row]').remove();
            reindexRows();
            updateTotal();
        });

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var rows = container.querySelectorAll('[data-item-row]');
                var lastRow = rows[rows.length - 1];
                var newRow = lastRow.cloneNode(true);
                newRow.querySelectorAll('[data-searchable-select]').forEach(function (r) {
                    r.removeAttribute('data-searchable-initialized');
                });
                newRow.removeAttribute('data-cat-init');
                resetRow(newRow);
                container.appendChild(newRow);
                reindexRows();
                initSearchableSelects();
                initItemCategoryFilter();
                updateTotal();
                var amountInput = newRow.querySelector('.item-amount');
                if (amountInput) amountInput.focus();
            });
        }

        container.addEventListener('input', function (e) {
            if (e.target.classList && e.target.classList.contains('item-amount')) {
                updateTotal();
            }
        });

        updateTotal();
    }

    function initBranchAgentCascade() {
        var branch = document.getElementById('branch_id');
        var agen = document.getElementById('agen_id');
        if (!branch || !agen) return;

        function apply() {
            var b = branch.value;
            var sel = agen.selectedOptions && agen.selectedOptions[0];
            var keep = sel && (sel.getAttribute('data-branch') === b || !sel.getAttribute('data-branch'));
            Array.prototype.forEach.call(agen.options, function (o) {
                var br = o.getAttribute('data-branch');
                o.style.display = (br === null || br === b) ? '' : 'none';
            });
            if (agen.value && !keep) agen.value = '';
        }

        branch.addEventListener('change', apply);
        apply();
    }

    function initProofPreview() {
        document.querySelectorAll('[data-proof-input]').forEach(function (input) {
            var preview = document.querySelector(input.getAttribute('data-proof-preview'));
            if (!preview) return;
            input.addEventListener('change', function () {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        preview.src = e.target.result;
                        preview.style.display = '';
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            });
        });
    }

    function init() {
        initSearchableSelects();
        initItemRows();
        initItemCategoryFilter();
        initBranchAgentCascade();
        initProofPreview();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.DonationForm = {
        init: init,
        initSearchableSelects: initSearchableSelects,
        initItemRows: initItemRows,
        initItemCategoryFilter: initItemCategoryFilter,
        initBranchAgentCascade: initBranchAgentCascade,
        initProofPreview: initProofPreview,
    };
})();
