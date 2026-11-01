/**
 * Opens and closes the tabs on smaller screens.
 */
document.addEventListener('click', function (event) {
    const toggle = event.target.closest('.eba-tabs__toggle');

    document.querySelectorAll('.eba-tabs.is-open').forEach(function (tabs) {
        if (!toggle || !tabs.contains(toggle)) {
            setOpen(tabs, false);
        }
    });

    if (toggle) {
        const tabs = toggle.closest('.eba-tabs');

        setOpen(tabs, !tabs.classList.contains('is-open'));
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.eba-tabs.is-open').forEach(function (tabs) {
        setOpen(tabs, false);
        tabs.querySelector('.eba-tabs__toggle').focus();
    });
});

function setOpen(tabs, open) {
    tabs.classList.toggle('is-open', open);
    tabs.querySelector('.eba-tabs__toggle').setAttribute('aria-expanded', open ? 'true' : 'false');
}

/**
 * Adds a copy button to license key fields (EDD Software Licensing).
 */
const COPY_ICON = '<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
const COPIED_ICON = '<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';
const l10n = window.ebaL10n || { copy: 'Copy license key', copied: 'Copied!', failed: 'Couldn\'t copy, press Ctrl+C (or Cmd+C) to copy it.' };

document.querySelectorAll('input.edd_sl_license_key').forEach(function (input) {
    // On the Manage Sites overview, the key is hidden until its key icon is clicked.
    if (input.closest('.view-key-wrapper')) {
        return;
    }

    const wrapper = document.createElement('span');
    const button = document.createElement('button');

    wrapper.className = 'eba-copy';
    button.type = 'button';
    button.className = 'eba-copy__button';
    button.title = l10n.copy;
    button.setAttribute('aria-label', l10n.copy);
    button.innerHTML = COPY_ICON;

    input.parentNode.insertBefore(wrapper, input);
    wrapper.append(input, button);

    button.addEventListener('click', function () {
        copy(input).then(function () {
            setState(button, 'is-copied', l10n.copied, COPIED_ICON);
        }, function () {
            // Select the key, so it can be copied by hand.
            input.focus();
            input.select();
            setState(button, 'is-failed', l10n.failed, COPY_ICON);
        });
    });
});

/**
 * Shows the result of copying on the button for 2 seconds.
 */
function setState(button, state, label, icon) {
    button.classList.remove('is-copied', 'is-failed');
    button.classList.add(state);
    button.title = label;
    button.setAttribute('aria-label', label);
    button.innerHTML = icon;

    clearTimeout(button.timeout);
    button.timeout = setTimeout(function () {
        button.classList.remove(state);
        button.title = l10n.copy;
        button.setAttribute('aria-label', l10n.copy);
        button.innerHTML = COPY_ICON;
    }, 2000);
}

/**
 * Copies the license key, with the fallback if the Clipboard API isn't available or not allowed.
 */
function copy(input) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(input.value).catch(function () {
            return fallbackCopy(input);
        });
    }

    return fallbackCopy(input);
}

/**
 * For browsers without the Clipboard API, or when it's not allowed.
 */
function fallbackCopy(input) {
    input.select();

    let copied = false;

    try {
        copied = document.execCommand('copy');
    } catch (e) {
        copied = false;
    }

    return copied ? Promise.resolve() : Promise.reject();
}

/**
 * Tables on smaller screens: each row becomes a card, with the column's name next to each value. The names are taken
 * from the table's header and shown with CSS (data-label). Empty cells are hidden.
 */
document.querySelectorAll('.eba__content table').forEach(function (table) {
    const labels = [];

    // A header spanning more columns (colspan) labels each of them.
    table.querySelectorAll('thead th').forEach(function (th) {
        for (let i = 0; i < (th.colSpan || 1); i++) {
            labels.push(th.textContent.trim());
        }
    });

    if (!labels.length) {
        return;
    }

    table.classList.add('eba-table');

    table.querySelectorAll('tbody tr').forEach(function (row) {
        let column = 0;

        Array.from(row.children).forEach(function (cell) {
            if (!cell.hasAttribute('data-label')) {
                cell.setAttribute('data-label', labels[column] || '');
            }

            column += cell.colSpan || 1;

            if (!cell.textContent.trim() && !cell.querySelector('a, img, svg, input, button, select, textarea, iframe, video')) {
                cell.classList.add('eba-table__empty');
            }

            // Wrap the cell's content, so it stays together next to its label.
            const value = document.createElement('div');

            value.className = 'eba-table__value';
            value.append.apply(value, Array.from(cell.childNodes));
            cell.append(value);
        });
    });
});
