{{-- Editors assets: loaded once per page --}}
{{-- Editors are loaded lazily via window.Voyager.loadEditors() when needed --}}
@once('voyager-editors-assets')
<script>
    (function () {
        const loadEditors = function () {
            if (!document.querySelector('.ace_editor')) {
                return;
            }
            if (!window.Voyager || typeof window.Voyager.loadEditors !== 'function') {
                return;
            }
            window.Voyager.loadEditors()
                .then(function (module) {
                    const api = (module && typeof module.initAceEditors === 'function')
                        ? module
                        : (window.Voyager && window.Voyager.editors ? window.Voyager.editors : null);
                    if (api && typeof api.initAceEditors === 'function') {
                        api.initAceEditors(document);
                    }
                })
                .catch(function (error) {
                    console.error('[Voyager] Failed to load editors bundle', error);
                });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadEditors);
        } else {
            loadEditors();
        }
    })();
</script>
@endonce
