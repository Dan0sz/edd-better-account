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
const l10n = window.ebaL10n || { copy: 'Copy license key', copied: 'Copied!' };

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
            button.classList.add('is-copied');
            button.title = l10n.copied;
            button.setAttribute('aria-label', l10n.copied);
            button.innerHTML = COPIED_ICON;

            clearTimeout(button.timeout);
            button.timeout = setTimeout(function () {
                button.classList.remove('is-copied');
                button.title = l10n.copy;
                button.setAttribute('aria-label', l10n.copy);
                button.innerHTML = COPY_ICON;
            }, 2000);
        });
    });
});

function copy(input) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(input.value);
    }

    // Fallback for browsers without the Clipboard API.
    input.select();
    document.execCommand('copy');

    return Promise.resolve();
}
