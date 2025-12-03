const resolveMatchHeightGroups = () => {
    if (typeof document === 'undefined') {
        return [];
    }
    const groups = [];
    const classElements = Array.from(document.querySelectorAll('.match-height'));
    if (classElements.length > 1) {
        groups.push(classElements);
    }
    const grouped = {};
    document.querySelectorAll('[data-match-height], [data-mh]').forEach((element) => {
        const key = element.getAttribute('data-match-height') || element.getAttribute('data-mh');
        if (!key) {
            return;
        }
        if (!grouped[key]) {
            grouped[key] = [];
        }
        grouped[key].push(element);
    });
    Object.keys(grouped).forEach((key) => {
        if (grouped[key].length > 1) {
            groups.push(grouped[key]);
        }
    });
    return groups;
};

const applyMatchHeight = () => {
    resolveMatchHeightGroups().forEach((elements) => {
        let maxHeight = 0;
        elements.forEach((element) => {
            element.style.minHeight = '';
            element.style.height = '';
            const rect = element.getBoundingClientRect();
            if (rect.height > maxHeight) {
                maxHeight = rect.height;
            }
        });
        if (maxHeight <= 0) {
            return;
        }
        elements.forEach((element) => {
            element.style.minHeight = `${maxHeight}px`;
        });
    });
};

let matchHeightScheduled = false;
export const initMatchHeight = () => {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }
    const scheduleUpdate = () => {
        if (matchHeightScheduled) {
            return;
        }
        matchHeightScheduled = true;
        const runner = () => {
            matchHeightScheduled = false;
            applyMatchHeight();
        };
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(runner);
        } else {
            setTimeout(runner, 60);
        }
    };
    applyMatchHeight();
    window.addEventListener('resize', scheduleUpdate);
};
