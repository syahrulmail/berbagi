(function () {
    'use strict';

    var modal = document.getElementById('donation-download-modal');
    var openBtns = document.querySelectorAll('[data-donation-download-open]');

    if (!modal || openBtns.length === 0) return;

    var form = document.getElementById('donation-download-form');
    var allBox = document.getElementById('donation-download-all-branches');
    var branchBoxes = modal.querySelectorAll('input[name="branch_ids[]"]');
    var agentSelect = document.getElementById('donation-download-agen');
    var agentOptions = agentSelect ? agentSelect.querySelectorAll('option[data-branch]') : [];
    var closeBtns = modal.querySelectorAll('[data-donation-download-close]');

    function selectedBranchIds() {
        return Array.prototype.filter.call(branchBoxes, function (box) {
            return box.checked;
        }).map(function (box) {
            return box.value;
        });
    }

    function applyAgentFilter() {
        if (!agentSelect || agentOptions.length === 0) return;

        var branches = selectedBranchIds();

        Array.prototype.forEach.call(agentOptions, function (option) {
            var match = branches.length === 0
                || branches.indexOf(option.getAttribute('data-branch')) !== -1;
            option.hidden = !match;
            option.disabled = !match;
        });

        var selected = agentSelect.options[agentSelect.selectedIndex];
        if (selected && selected.disabled) agentSelect.value = '';
    }

    function open() {
        applyAgentFilter();
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    Array.prototype.forEach.call(openBtns, function (btn) {
        btn.addEventListener('click', open);
    });

    Array.prototype.forEach.call(closeBtns, function (btn) {
        btn.addEventListener('click', close);
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });

    if (allBox) {
        allBox.addEventListener('change', function () {
            Array.prototype.forEach.call(branchBoxes, function (box) {
                box.checked = allBox.checked;
            });
            applyAgentFilter();
        });
    }

    Array.prototype.forEach.call(branchBoxes, function (box) {
        box.addEventListener('change', function () {
            var allChecked = branchBoxes.length > 0
                && Array.prototype.every.call(branchBoxes, function (item) {
                    return item.checked;
                });

            if (allBox) allBox.checked = allChecked;
            applyAgentFilter();
        });
    });

    if (form) {
        form.addEventListener('submit', function () {
            setTimeout(close, 300);
        });
    }
})();
