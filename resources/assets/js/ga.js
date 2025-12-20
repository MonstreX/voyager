import '../sass/ga.scss';

const getConfig = () => {
    const el = document.getElementById('voyager-analytics-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (error) {
        console.error('[VoyagerGA] Failed to parse config', error);
        return null;
    }
};

const getIntlFormatter = (locale, options, fallback) => {
    if (window.Intl && window.Intl.DateTimeFormat) {
        try {
            return new window.Intl.DateTimeFormat(locale || 'en', options);
        } catch {
            return fallback;
        }
    }
    return fallback;
};

const ensureScript = (src, key) => {
    const id = 'voyager-ga-' + key;
    const existing = document.getElementById(id) || document.querySelector(`script[src="${src}"]`);
    if (existing) return Promise.resolve();

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.id = id;
        script.async = true;
        script.src = src;
        script.onload = () => resolve();
        script.onerror = reject;
        document.head.appendChild(script);
    });
};

const ensureGapiAnalytics = () => {
    const g = window.gapi || (window.gapi = {});
    if (!g.analytics) {
        g.analytics = {
            q: [],
            ready: function (fn) {
                this.q.push(fn);
            },
        };
    }

    const loadAnalytics = () => {
        if (typeof g.load === 'function') {
            try {
                g.load('analytics');
            } catch (error) {
                console.error('[VoyagerGA] gapi.load failed', error);
            }
        }
    };

    return ensureScript('https://apis.google.com/js/platform.js', 'platform')
        .then(loadAnalytics)
        .catch((error) => {
            console.error('[VoyagerGA] Failed to load platform.js', error);
        });
};

const ensureAnalyticsClient = () => {
    const g = window.gapi;
    if (!g || !g.client || typeof g.client.load !== 'function') {
        return Promise.resolve();
    }
    return new Promise((resolve) => {
        try {
            g.client.load('analytics', 'v3', () => resolve());
        } catch (error) {
            console.error('[VoyagerGA] Failed to load analytics v3 client', error);
            resolve();
        }
    });
};

const query = (params) => {
    return window.gapi.client.analytics.data.ga.get(params).then((response) => response.result);
};

const formatDate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const datasetsHaveValues = (datasets) => {
    return (datasets || []).some((dataset) => (dataset.values || []).some((v) => typeof v === 'number' && v > 0));
};

const normalizeMetricRows = (rows, length) => {
    const values = new Array(length).fill(0);
    (rows || []).forEach((row) => {
        const nth = row && row[1] ? parseInt(row[1], 10) : NaN;
        const value = row && row[2] ? parseInt(row[2], 10) : NaN;
        if (!Number.isFinite(nth) || nth < 1 || nth > length) return;
        values[nth - 1] = Number.isFinite(value) ? value : 0;
    });
    return values;
};

const mapMonthlyMetrics = (rows, length) => {
    const values = new Array(length).fill(0);
    (rows || []).forEach((row) => {
        const nth = row && row[1] ? parseInt(row[1], 10) : NaN;
        const value = row && row[2] ? parseInt(row[2], 10) : NaN;
        if (!Number.isFinite(nth) || nth < 1 || nth > length) return;
        values[nth - 1] = Number.isFinite(value) ? value : 0;
    });
    return values;
};

const buildCategorySeries = (rows, colors) => {
    const out = [];
    (rows || []).forEach((row, index) => {
        if (!row || row.length < 2) return;
        const label = row[0];
        const value = parseInt(row[1], 10);
        if (!label || !Number.isFinite(value)) return;
        out.push({
            label,
            value,
            color: colors[index % colors.length],
        });
    });
    return out;
};

const showNoData = (containerId, text) => {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = `<div style="padding: 20px; text-align:center; color:#718096;">${text || 'No results'}</div>`;
};

const makeCanvas = (containerId) => {
    const container = document.getElementById(containerId);
    if (!container) return null;
    const rect = container.getBoundingClientRect();
    const width = Math.max(240, Math.floor(rect.width || container.clientWidth || 600));
    const height = Math.max(180, Math.floor(rect.height || container.clientHeight || 260));
    container.innerHTML = '';
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    container.appendChild(canvas);
    const ctx = canvas.getContext('2d');
    ctx._voyagerWidth = width;
    ctx._voyagerHeight = height;
    return ctx;
};

const generateLegend = (legendId, items) => {
    const legend = document.getElementById(legendId);
    if (!legend) return;
    legend.innerHTML = '';
    (items || []).forEach((item) => {
        const li = document.createElement('li');
        li.style.display = 'flex';
        li.style.alignItems = 'center';
        li.style.gap = '8px';
        const dot = document.createElement('span');
        dot.style.display = 'inline-block';
        dot.style.width = '10px';
        dot.style.height = '10px';
        dot.style.borderRadius = '50%';
        dot.style.background = item.color;
        const text = document.createElement('span');
        text.textContent = item.label;
        li.appendChild(dot);
        li.appendChild(text);
        legend.appendChild(li);
    });
};

const drawLineChart = (containerId, legendId, labels, datasets) => {
    const ctx = makeCanvas(containerId);
    if (!ctx) return;
    const width = ctx._voyagerWidth;
    const height = ctx._voyagerHeight;
    const padding = 32;
    const chartWidth = Math.max(10, width - padding * 2);
    const chartHeight = Math.max(10, height - padding * 2 - 20);

    let maxValue = 0;
    datasets.forEach((dataset) => {
        (dataset.values || []).forEach((v) => {
            if (typeof v === 'number' && v > maxValue) maxValue = v;
        });
    });
    if (maxValue === 0) maxValue = 1;

    ctx.strokeStyle = '#E2E8F0';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i += 1) {
        const y = padding + (chartHeight / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(padding + chartWidth, y);
        ctx.stroke();
    }

    const stepX = chartWidth / Math.max(1, labels.length - 1);
    datasets.forEach((dataset) => {
        ctx.strokeStyle = dataset.color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        (dataset.values || []).forEach((value, index) => {
            const x = padding + stepX * index;
            const y = padding + chartHeight - (value / maxValue) * chartHeight;
            if (index === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.stroke();
    });

    ctx.fillStyle = '#4A5568';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'center';
    labels.forEach((label, index) => {
        const x = padding + stepX * index;
        ctx.fillText(label, x, padding + chartHeight + 16);
    });

    generateLegend(legendId, datasets);
};

const drawBarChart = (containerId, legendId, labels, datasets) => {
    const ctx = makeCanvas(containerId);
    if (!ctx) return;
    const width = ctx._voyagerWidth;
    const height = ctx._voyagerHeight;
    const padding = 32;
    const chartWidth = Math.max(10, width - padding * 2);
    const chartHeight = Math.max(10, height - padding * 2 - 20);

    let maxValue = 0;
    datasets.forEach((dataset) => {
        (dataset.values || []).forEach((v) => {
            if (typeof v === 'number' && v > maxValue) maxValue = v;
        });
    });
    if (maxValue === 0) maxValue = 1;

    ctx.strokeStyle = '#E2E8F0';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i += 1) {
        const y = padding + (chartHeight / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(padding + chartWidth, y);
        ctx.stroke();
    }

    const groupWidth = chartWidth / labels.length;
    const barWidth = Math.max(4, (groupWidth - 6) / datasets.length);
    const originY = padding + chartHeight;
    datasets.forEach((dataset, datasetIndex) => {
        ctx.fillStyle = dataset.color;
        (dataset.values || []).forEach((value, index) => {
            const x = padding + groupWidth * index + datasetIndex * barWidth;
            const barHeight = (value / maxValue) * chartHeight;
            ctx.fillRect(x, originY - barHeight, barWidth - 2, barHeight);
        });
    });

    ctx.fillStyle = '#4A5568';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'center';
    labels.forEach((label, index) => {
        const x = padding + groupWidth * index + groupWidth / 2;
        ctx.fillText(label, x, originY + 16);
    });

    generateLegend(legendId, datasets);
};

const drawDonutChart = (containerId, legendId, items) => {
    const ctx = makeCanvas(containerId);
    if (!ctx) return;
    const width = ctx._voyagerWidth;
    const height = ctx._voyagerHeight;
    const total = items.reduce((sum, item) => sum + (item.value || 0), 0);
    if (!total) {
        generateLegend(legendId, []);
        return;
    }

    const radius = Math.min(width, height) / 2 - 10;
    const centerX = width / 2;
    const centerY = height / 2;
    let startAngle = -Math.PI / 2;

    items.forEach((item) => {
        const sliceAngle = (item.value / total) * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        ctx.closePath();
        ctx.fillStyle = item.color;
        ctx.fill();
        startAngle += sliceAngle;
    });

    ctx.beginPath();
    ctx.fillStyle = '#fff';
    ctx.arc(centerX, centerY, radius * 0.55, 0, Math.PI * 2);
    ctx.fill();

    generateLegend(legendId, items);
};

const getWeekRanges = () => {
    const now = new Date();
    const end = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const day = end.getDay(); // 0..6
    const diffToMonday = (day + 6) % 7;
    const thisWeekStart = new Date(end);
    thisWeekStart.setDate(end.getDate() - diffToMonday);
    const thisWeekEnd = new Date(thisWeekStart);
    thisWeekEnd.setDate(thisWeekStart.getDate() + 6);
    const lastWeekStart = new Date(thisWeekStart);
    lastWeekStart.setDate(thisWeekStart.getDate() - 7);
    const lastWeekEnd = new Date(thisWeekEnd);
    lastWeekEnd.setDate(thisWeekEnd.getDate() - 7);
    return { thisWeekStart, thisWeekEnd, lastWeekStart, lastWeekEnd };
};

const getYearRanges = () => {
    const now = new Date();
    const thisYearStart = new Date(now.getFullYear(), 0, 1);
    const thisYearEnd = new Date(now.getFullYear(), 11, 31);
    const lastYearStart = new Date(now.getFullYear() - 1, 0, 1);
    const lastYearEnd = new Date(now.getFullYear() - 1, 11, 31);
    return { thisYearStart, thisYearEnd, lastYearStart, lastYearEnd };
};

const initDashboardAnalytics = () => {
    const config = getConfig();
    if (!config || !config.enabled) return;

    const authContainer = document.getElementById('embed-api-auth-container');
    const dashboard = document.getElementById('analytics-dashboard');
    const viewName = document.getElementById('view-name');

    if (!authContainer || !dashboard) return;

    ensureGapiAnalytics();

    window.gapi.analytics.ready(function () {
        ensureAnalyticsClient().then(() => {});

        window.gapi.analytics.auth.authorize({
            container: 'embed-api-auth-container',
            clientid: config.clientId,
        });

        const locale = (document.documentElement && document.documentElement.lang) ? document.documentElement.lang : 'en';
        const weekdayFormatter = getIntlFormatter(locale, { weekday: 'short' }, null);
        const monthFormatter = getIntlFormatter(locale, { month: 'short' }, null);
        const fallbackWeekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const fallbackMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const noDataText = config.i18n?.noResults || 'No results';

        const categoryColors = ['#4D5360', '#949FB1', '#D4CCC5', '#E2EAE9', '#F7464A'];
        const lineColors = { current: '#4299E1', previous: '#A0AEC0' };

        const buildWeekdayLabels = (start, count) => {
            const labels = [];
            const cursor = new Date(start);
            for (let i = 0; i < count; i += 1) {
                const label = weekdayFormatter
                    ? weekdayFormatter.format(cursor)
                    : fallbackWeekdays[cursor.getDay()];
                labels.push(label);
                cursor.setDate(cursor.getDate() + 1);
            }
            return labels;
        };

        const buildMonthLabels = () => {
            const labels = [];
            for (let i = 0; i < 12; i += 1) {
                const date = new Date(2000, i, 1);
                labels.push(monthFormatter ? monthFormatter.format(date) : fallbackMonths[i]);
            }
            return labels;
        };

        const renderWeekOverWeekChart = (ids) => {
            const ranges = getWeekRanges();
            const currentWeek = query({
                ids,
                dimensions: 'ga:date,ga:nthDay',
                metrics: 'ga:users',
                'start-date': formatDate(ranges.thisWeekStart),
                'end-date': formatDate(ranges.thisWeekEnd),
            });
            const lastWeek = query({
                ids,
                dimensions: 'ga:date,ga:nthDay',
                metrics: 'ga:users',
                'start-date': formatDate(ranges.lastWeekStart),
                'end-date': formatDate(ranges.lastWeekEnd),
            });

            Promise.all([currentWeek, lastWeek])
                .then((results) => {
                    const labels = buildWeekdayLabels(ranges.lastWeekStart, 7);
                    const datasets = [
                        {
                            label: config.labels?.lastWeek || 'Last week',
                            color: lineColors.previous,
                            values: normalizeMetricRows(results[1] && results[1].rows, labels.length),
                        },
                        {
                            label: config.labels?.thisWeek || 'This week',
                            color: lineColors.current,
                            values: normalizeMetricRows(results[0] && results[0].rows, labels.length),
                        },
                    ];
                    if (!datasetsHaveValues(datasets)) {
                        showNoData('chart-1-container', noDataText);
                        generateLegend('legend-1-container', []);
                        return;
                    }
                    drawLineChart('chart-1-container', 'legend-1-container', labels, datasets);
                })
                .catch(() => showNoData('chart-1-container', noDataText));
        };

        const renderYearOverYearChart = (ids) => {
            const ranges = getYearRanges();
            const thisYear = query({
                ids,
                dimensions: 'ga:month,ga:nthMonth',
                metrics: 'ga:users',
                'start-date': formatDate(ranges.thisYearStart),
                'end-date': formatDate(ranges.thisYearEnd),
            });
            const lastYear = query({
                ids,
                dimensions: 'ga:month,ga:nthMonth',
                metrics: 'ga:users',
                'start-date': formatDate(ranges.lastYearStart),
                'end-date': formatDate(ranges.lastYearEnd),
            });

            Promise.all([thisYear, lastYear])
                .then((results) => {
                    const labels = buildMonthLabels();
                    const datasets = [
                        {
                            label: config.labels?.lastYear || 'Last year',
                            color: lineColors.previous,
                            values: mapMonthlyMetrics(results[1] && results[1].rows, labels.length),
                        },
                        {
                            label: config.labels?.thisYear || 'This year',
                            color: lineColors.current,
                            values: mapMonthlyMetrics(results[0] && results[0].rows, labels.length),
                        },
                    ];
                    if (!datasetsHaveValues(datasets)) {
                        showNoData('chart-2-container', noDataText);
                        generateLegend('legend-2-container', []);
                        return;
                    }
                    drawBarChart('chart-2-container', 'legend-2-container', labels, datasets);
                })
                .catch(() => showNoData('chart-2-container', noDataText));
        };

        const renderTopBrowsersChart = (ids) => {
            query({
                ids,
                dimensions: 'ga:browser',
                metrics: 'ga:pageviews',
                sort: '-ga:pageviews',
                'max-results': 5,
            })
                .then((result) => {
                    const items = buildCategorySeries(result && result.rows, categoryColors);
                    if (!items.length) {
                        showNoData('chart-3-container', noDataText);
                        generateLegend('legend-3-container', []);
                        return;
                    }
                    drawDonutChart('chart-3-container', 'legend-3-container', items);
                })
                .catch(() => showNoData('chart-3-container', noDataText));
        };

        const renderTopCountriesChart = (ids) => {
            query({
                ids,
                dimensions: 'ga:country',
                metrics: 'ga:sessions',
                sort: '-ga:sessions',
                'max-results': 5,
            })
                .then((result) => {
                    const items = buildCategorySeries(result && result.rows, categoryColors);
                    if (!items.length) {
                        showNoData('chart-4-container', noDataText);
                        generateLegend('legend-4-container', []);
                        return;
                    }
                    drawDonutChart('chart-4-container', 'legend-4-container', items);
                })
                .catch(() => showNoData('chart-4-container', noDataText));
        };

        const viewSelectorContainer = document.getElementById('view-selector-container');
        if (!viewSelectorContainer) return;

        const createSelect = (id) => {
            const select = document.createElement('select');
            select.id = id;
            select.className = 'form-control';
            return select;
        };

        const accountSelect = createSelect('voyager-ga-account');
        const propertySelect = createSelect('voyager-ga-property');
        const viewSelect = createSelect('voyager-ga-view');

        viewSelectorContainer.innerHTML = '';
        viewSelectorContainer.appendChild(accountSelect);
        viewSelectorContainer.appendChild(propertySelect);
        viewSelectorContainer.appendChild(viewSelect);

        const fillSelect = (select, items) => {
            select.innerHTML = '';
            (items || []).forEach((item) => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                select.appendChild(opt);
            });
        };

        const listAccounts = () => window.gapi.client.analytics.management.accounts.list().then((r) => r.result.items || []);
        const listProperties = (accountId) =>
            window.gapi.client.analytics.management.webproperties.list({ accountId }).then((r) => r.result.items || []);
        const listViews = (accountId, webPropertyId) =>
            window.gapi.client.analytics.management.profiles.list({ accountId, webPropertyId }).then((r) => r.result.items || []);

        const state = { accounts: [], properties: [], views: [] };

        const refreshViews = () => {
            const accountId = accountSelect.value;
            const propertyId = propertySelect.value;
            if (!accountId || !propertyId) return;
            listViews(accountId, propertyId).then((views) => {
                state.views = views;
                fillSelect(viewSelect, views);
                viewSelect.dispatchEvent(new Event('change'));
            });
        };

        const refreshProperties = () => {
            const accountId = accountSelect.value;
            if (!accountId) return;
            listProperties(accountId).then((properties) => {
                state.properties = properties;
                fillSelect(propertySelect, properties);
                refreshViews();
            });
        };

        accountSelect.addEventListener('change', refreshProperties);
        propertySelect.addEventListener('change', refreshViews);
        viewSelect.addEventListener('change', () => {
            const selectedView = state.views.find((v) => v.id === viewSelect.value);
            const selectedProperty = state.properties.find((p) => p.id === propertySelect.value);
            if (viewName && selectedProperty && selectedView) {
                viewName.textContent = `${selectedProperty.name} (${selectedView.name})`;
            }

            const ids = selectedView ? `ga:${selectedView.id}` : null;
            if (!ids) return;

            authContainer.style.display = 'none';
            dashboard.style.display = 'block';

            renderWeekOverWeekChart(ids);
            renderYearOverYearChart(ids);
            renderTopBrowsersChart(ids);
            renderTopCountriesChart(ids);
        });

        listAccounts()
            .then((accounts) => {
                state.accounts = accounts;
                if (!accounts.length) return;
                fillSelect(accountSelect, accounts);
                refreshProperties();
            })
            .catch((error) => {
                console.error('[VoyagerGA] Failed to load accounts', error);
            });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('analytics-dashboard') || document.getElementById('embed-api-auth-container')) {
        initDashboardAnalytics();
    }
});
