/*--------------------
|
| HELPERS
|
--------------------*/

const displayAlert = function(alert, alerter) {
    let alertMethod = alerter[alert.type];

    if (alertMethod) {
        return alertMethod(alert.message);
    }

    alerter.error("No alert method found for alert type: " + alert.type);
}

const displayAlerts = function(alerts, alerter, type) {
    if (type) {
        // Only display alerts of this type...
        alerts = alerts.filter(function(alert) {
            return type == alert.type;
        });
    }

    alerts.forEach(function(alert) {
        displayAlert(alert, alerter);
    });
}

const bootstrapAlerter = function(customOptions = {}) {
    const options = Object.assign({
        alertsContainer: '#alertsContainer',
        dismissible: false,
        dismissButton: '<button class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
    }, customOptions);

    const dismissibleClass = options.dismissible ? ' alert-dismissible' : '';
    const dismissButton = options.dismissible ? options.dismissButton : '';

    function resolveAlertsContainer() {
        if (typeof options.alertsContainer === 'string') {
            return document.querySelector(options.alertsContainer);
        }

        if (options.alertsContainer instanceof HTMLElement) {
            return options.alertsContainer;
        }

        return null;
    }

    function notify(type, message) {
        const container = resolveAlertsContainer();
        if (!container) {
            console.warn('bootstrapAlerter: alerts container not found');
            return;
        }

        const alert = '<div class="alert alert-'  + type +  dismissibleClass + '" role="alert">'
                        + dismissButton + message +
                    '</div>';

        container.insertAdjacentHTML('beforeend', alert);
    }

    return {
        success(message) {
            notify('success', message);
        },
        info(message) {
            notify('info', message);
        },
        warning(message) {
            notify('warning', message);
        },
        error(message) {
            notify('danger', message);
        }
    };
}

const setImageValue = function(url) {
    const openButton = document.querySelector('.mce-btn.mce-open');
    if (!openButton || !openButton.parentElement) {
        return;
    }

    const textbox = openButton.parentElement.querySelector('.mce-textbox');

    if (textbox) {
        textbox.value = url;
    }
}

export { setImageValue, displayAlert, displayAlerts, bootstrapAlerter };
