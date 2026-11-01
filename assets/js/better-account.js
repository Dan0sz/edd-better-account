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
