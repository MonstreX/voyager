// This file MUST be imported first to ensure jQuery is globally available
import jQuery from 'jquery';

// Make jQuery available globally BEFORE any other modules load
if (typeof window !== 'undefined') {
    window.jQuery = jQuery;
    window.$ = jQuery;
}

export default jQuery;
