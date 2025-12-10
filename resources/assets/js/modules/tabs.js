export function initTabs(container = document) {
    // Helper to safely process a single node
    const processNode = (node) => {
        // Only process ELEMENT_NODE (1) and DOCUMENT_NODE (9)
        // Skip text nodes, comments, etc. which don't have querySelector
        if (!node || (node.nodeType !== 1 && node.nodeType !== 9)) {
            return;
        }

        // 1. Check for validation errors and switch to the first tab with an error
        // We use try-catch to be extra safe against any weird DOM states
        try {
            const firstError = node.querySelector('.form-group.has-error');
            if (firstError) {
                const tabPane = firstError.closest('.tab-pane');
                if (tabPane && tabPane.id) {
                    // Find the tab link. We search in the document because the nav might be outside 
                    // the updated fragment (e.g., if only the form body updated).
                    const tabLink = document.querySelector(`.nav-tabs a[href="#${tabPane.id}"]`);
                    
                    if (tabLink) {
                        // Ensure Bootstrap tab plugin is available or manually switch
                        if (typeof $ !== 'undefined' && $(tabLink).tab) {
                            $(tabLink).tab('show');
                        } else {
                            tabLink.click();
                        }
                    }
                }
            }
        } catch (e) {
            console.warn('[Voyager Tabs] Error processing node:', e);
        }
    };

    // Handle different input types
    if (container instanceof NodeList || Array.isArray(container)) {
        container.forEach(node => processNode(node));
    } else {
        processNode(container);
    }
}

export function subscribeToEvents(events) {
    events.on('dom:updated', (element) => {
        initTabs(element);
    });
}