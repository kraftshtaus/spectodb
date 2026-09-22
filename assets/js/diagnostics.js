document.addEventListener('DOMContentLoaded', () => {

    const sourceButtons = Array.from(
        document.querySelectorAll(
            '.diagnostic-filter'
        )
    );

    const severitySelect =
        document.getElementById(
            'severityFilter'
        );

    const items = Array.from(
        document.querySelectorAll(
            '.diagnostic-item'
        )
    );

    const noResults =
        document.getElementById(
            'noFilteredResults'
        );

    let activeSource = 'all';

sourceButtons.forEach(button => {

    button.addEventListener(
        'click',
        () => {

            if (button.disabled) {
                return;
            }

            activeSource =
                button.dataset.filter
                || 'all';

            sourceButtons.forEach(item => {
                item.classList.remove(
                    'active'
                );
            });

            button.classList.add(
                'active'
            );

            applyFilters();
        }
    );

});

    severitySelect?.addEventListener(
        'change',
        applyFilters
    );

    function applyFilters() {

        const severity =
            severitySelect?.value
            || 'all';

        let visible = 0;

        items.forEach(item => {

            const itemSource =
                item.dataset.source
                || '';

            const itemSeverity =
                item.dataset.severity
                || '';

            const sourceMatch =
                activeSource === 'all'
                || itemSource === activeSource;

            const severityMatch =
                severity === 'all'
                || itemSeverity === severity;

            const show =
                sourceMatch
                && severityMatch;

            item.hidden = !show;

            if (show) {
                visible++;
            }
        });

        if (noResults) {
            noResults.hidden =
                visible !== 0;
        }
    }

});