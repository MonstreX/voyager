/**
 * Simple Event Bus for Voyager
 * Allows components to subscribe to and emit custom events
 * Main use case: dom:updated event for dynamic content reinitialization
 */
class EventBus {
    constructor() {
        this.listeners = new Map();
    }

    /**
     * Subscribe to an event
     * @param {string} event - Event name
     * @param {Function} callback - Handler function
     * @returns {Function} Unsubscribe function
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, new Set());
        }

        this.listeners.get(event).add(callback);

        // Return unsubscribe function
        return () => this.off(event, callback);
    }

    /**
     * Subscribe to an event (one-time only)
     * @param {string} event - Event name
     * @param {Function} callback - Handler function
     * @returns {Function} Unsubscribe function
     */
    once(event, callback) {
        const wrapper = (...args) => {
            this.off(event, wrapper);
            callback(...args);
        };

        return this.on(event, wrapper);
    }

    /**
     * Unsubscribe from an event
     * @param {string} event - Event name
     * @param {Function} callback - Handler function to remove
     */
    off(event, callback) {
        if (!this.listeners.has(event)) {
            return;
        }

        const eventListeners = this.listeners.get(event);
        eventListeners.delete(callback);

        // Clean up empty event sets
        if (eventListeners.size === 0) {
            this.listeners.delete(event);
        }
    }

    /**
     * Emit an event
     * @param {string} event - Event name
     * @param {...any} args - Arguments to pass to handlers
     */
    emit(event, ...args) {
        if (!this.listeners.has(event)) {
            return;
        }

        const eventListeners = this.listeners.get(event);
        eventListeners.forEach((callback) => {
            try {
                callback(...args);
            } catch (error) {
                console.error(`[Voyager EventBus] Error in "${event}" handler:`, error);
            }
        });
    }

    /**
     * Remove all listeners for an event, or all events if no event specified
     * @param {string} [event] - Event name (optional)
     */
    clear(event) {
        if (event) {
            this.listeners.delete(event);
        } else {
            this.listeners.clear();
        }
    }

    /**
     * Get listener count for an event
     * @param {string} event - Event name
     * @returns {number} Number of listeners
     */
    listenerCount(event) {
        return this.listeners.has(event) ? this.listeners.get(event).size : 0;
    }
}

// Export singleton instance
export const voyagerEvents = new EventBus();

// Also export class for testing
export default EventBus;
