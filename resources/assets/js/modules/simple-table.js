const DEFAULT_OPTIONS = {
    perPage: 25,
    perPageOptions: [10, 25, 50, 100],
    search: true,
    searchPlaceholder: '',
    order: null,
    lang: {
        show: 'Show',
        entries: 'entries',
        search: 'Search:',
        showing: 'Showing',
        to: 'to',
        of: 'of',
        results: 'results',
        prev: 'Previous',
        next: 'Next',
        first: 'First',
        last: 'Last'
    }
};

const UNSORTABLE_CLASSES = ['actions', 'no-sort', 'dt-not-orderable', 'js-no-sort'];

export default class SimpleTable {
    constructor(table, options = {}) {
        this.table = table;
        this.tbody = table.tBodies[0];
        if (!this.tbody) {
            return;
        }

        // Deep merge for options to handle nested lang object
        this.options = {
            ...DEFAULT_OPTIONS,
            ...options,
            lang: { ...DEFAULT_OPTIONS.lang, ...(options.lang || {}) }
        };

        this.options.perPage = parseInt(this.options.perPage, 10);
        if (isNaN(this.options.perPage) || this.options.perPage < 0) {
            this.options.perPage = 0;
        }

        this.rows = Array.from(this.tbody.rows);
        this.currentPage = 1;
        this.searchTerm = '';
        this.order = this.normalizeOrder(this.options.order);

        this.buildLayout();
        this.bindHeaderSorting();
        this.render();
    }

    normalizeOrder(order) {
        if (!order || !Array.isArray(order) || order.length < 2) {
            return null;
        }
        const column = parseInt(order[0], 10);
        if (isNaN(column)) {
            return null;
        }
        const direction = (order[1] || 'asc').toLowerCase() === 'desc' ? 'desc' : 'asc';
        return [column, direction];
    }

    buildLayout() {
        // 1. Header (Length & Search)
        this.header = document.createElement('div');
        this.header.className = 'row simple-table-header';

        // 1a. Length Menu (Left)
        const lengthCol = document.createElement('div');
        lengthCol.className = 'col-sm-6';
        
        if (this.options.perPage > 0 || this.options.perPageOptions.length > 0) {
            const lengthWrapper = document.createElement('div');
            lengthWrapper.className = 'simple-table-length';
            
            const lengthLabel = document.createElement('label');
            
            // Text: Show
            lengthLabel.appendChild(document.createTextNode(this.options.lang.show + ' '));

            // Select
            this.lengthSelect = document.createElement('select');
            this.lengthSelect.className = 'form-control input-sm';
            
            this.options.perPageOptions.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.text = opt;
                if (opt === this.options.perPage) {
                    option.selected = true;
                }
                this.lengthSelect.appendChild(option);
            });

            this.lengthSelect.addEventListener('change', (e) => {
                this.options.perPage = parseInt(e.target.value, 10);
                this.currentPage = 1;
                this.render();
            });

            lengthLabel.appendChild(this.lengthSelect);
            
            // Text: entries
            lengthLabel.appendChild(document.createTextNode(' ' + this.options.lang.entries));

            lengthWrapper.appendChild(lengthLabel);
            lengthCol.appendChild(lengthWrapper);
        }
        this.header.appendChild(lengthCol);

        // 1b. Search (Right)
        const searchCol = document.createElement('div');
        searchCol.className = 'col-sm-6';

        if (this.options.search !== false) {
            const searchWrapper = document.createElement('div');
            searchWrapper.className = 'simple-table-search';

            const searchLabel = document.createElement('label');
            
            // Text: Search:
            searchLabel.appendChild(document.createTextNode(this.options.lang.search + ' '));

            // Input
            this.searchInput = document.createElement('input');
            this.searchInput.type = 'search';
            this.searchInput.className = 'form-control input-sm';
            this.searchInput.placeholder = this.options.searchPlaceholder;
            
            this.searchInput.addEventListener('input', () => {
                this.searchTerm = this.searchInput.value.toLowerCase();
                this.currentPage = 1;
                this.render();
            });

            searchLabel.appendChild(this.searchInput);
            searchWrapper.appendChild(searchLabel);
            searchCol.appendChild(searchWrapper);
        }
        this.header.appendChild(searchCol);

        // Insert Header before table
        this.table.parentNode.insertBefore(this.header, this.table);


        // 2. Footer (Info & Pagination)
        this.footer = document.createElement('div');
        this.footer.className = 'row simple-table-footer';

        // 2a. Info (Left)
        const infoCol = document.createElement('div');
        infoCol.className = 'col-sm-6';
        
        this.infoElement = document.createElement('div');
        this.infoElement.className = 'simple-table-info';
        this.infoElement.setAttribute('role', 'status');
        this.infoElement.setAttribute('aria-live', 'polite');
        
        infoCol.appendChild(this.infoElement);
        this.footer.appendChild(infoCol);

        // 2b. Pagination (Right)
        const paginateCol = document.createElement('div');
        paginateCol.className = 'col-sm-6';
        
        const paginateWrapper = document.createElement('div');
        paginateWrapper.className = 'simple-table-pagination';

        this.paginationList = document.createElement('ul');
        this.paginationList.className = 'pagination';
        
        paginateWrapper.appendChild(this.paginationList);
        paginateCol.appendChild(paginateWrapper);
        this.footer.appendChild(paginateCol);

        // Insert Footer after table
        this.table.parentNode.insertBefore(this.footer, this.table.nextSibling);
    }

    bindHeaderSorting() {
        const headers = Array.from(this.table.querySelectorAll('thead th'));
        headers.forEach((th, index) => {
            if (UNSORTABLE_CLASSES.some(cls => th.classList.contains(cls))) {
                th.classList.add('simple-table-no-sort');
                return;
            }

            th.classList.add('simple-table-sortable');
            
            th.addEventListener('click', () => {
                let direction = 'asc';
                if (this.order && this.order[0] === index) {
                    direction = this.order[1] === 'asc' ? 'desc' : 'asc';
                }
                this.order = [index, direction];
                this.currentPage = 1;
                this.render();
            });
        });
        this.headers = headers;
    }

    render() {
        const filteredRows = this.getFilteredRows();
        const sortedRows = this.getSortedRows(filteredRows);
        this.totalRows = sortedRows.length;

        let visibleRows = sortedRows;
        if (this.options.perPage > 0) {
            this.totalPages = Math.max(1, Math.ceil(this.totalRows / this.options.perPage));
            if (this.currentPage > this.totalPages) {
                this.currentPage = this.totalPages;
            }
            const start = (this.currentPage - 1) * this.options.perPage;
            visibleRows = sortedRows.slice(start, start + this.options.perPage);
        } else {
            this.totalPages = 1;
            this.currentPage = 1;
        }

        const fragment = document.createDocumentFragment();
        visibleRows.forEach(row => fragment.appendChild(row));
        this.tbody.innerHTML = '';
        this.tbody.appendChild(fragment);

        this.updateInfo();
        this.updatePagination();
        this.updateHeaderIndicators();
    }

    getFilteredRows() {
        if (!this.searchTerm) {
            return this.rows.slice();
        }
        return this.rows.filter(row => row.textContent.toLowerCase().includes(this.searchTerm));
    }

    getCellValue(row, index) {
        const cell = row.cells[index];
        return cell ? cell.textContent.trim() : '';
    }

    getSortedRows(rows) {
        if (!this.order) {
            return rows.slice();
        }
        const [column, direction] = this.order;
        const sorted = rows.slice().sort((a, b) => {
            const aVal = this.getCellValue(a, column);
            const bVal = this.getCellValue(b, column);

            const aNum = parseFloat(aVal.replace(',', '.'));
            const bNum = parseFloat(bVal.replace(',', '.'));
            const bothNumeric = !isNaN(aNum) && !isNaN(bNum);

            if (bothNumeric) {
                return aNum - bNum;
            }

            return aVal.localeCompare(bVal, undefined, {numeric: true, sensitivity: 'base'});
        });

        if (direction === 'desc') {
            sorted.reverse();
        }

        return sorted;
    }

    updateInfo() {
        if (!this.infoElement) {
            return;
        }

        if (this.totalRows === 0) {
            this.infoElement.innerText = `0 ${this.options.lang.entries}`;
            return;
        }

        let infoText = '';
        if (this.options.perPage > 0) {
            const start = (this.currentPage - 1) * this.options.perPage + 1;
            const end = Math.min(this.currentPage * this.options.perPage, this.totalRows);
            infoText = `${this.options.lang.showing} ${start} ${this.options.lang.to} ${end} ${this.options.lang.of} ${this.totalRows} ${this.options.lang.entries}`;
        } else {
            infoText = `${this.options.lang.showing} ${this.totalRows} ${this.options.lang.entries}`;
        }
        this.infoElement.innerText = infoText;
    }

    updatePagination() {
        if (!this.paginationList) {
            return;
        }
        this.paginationList.innerHTML = '';

        const createPageItem = (text, page, type = 'number') => {
            const li = document.createElement('li');
            li.className = `simple-table-page-item ${type}`;
            if (type === 'previous' && this.currentPage <= 1) li.classList.add('disabled');
            if (type === 'next' && this.currentPage >= this.totalPages) li.classList.add('disabled');
            if (type === 'number' && page === this.currentPage) li.classList.add('active');

            const a = document.createElement('a');
            a.href = '#';
            a.innerText = text;
            a.setAttribute('tabindex', '0');
            
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (li.classList.contains('disabled') || li.classList.contains('active')) return;
                
                this.currentPage = page;
                this.render();
            });

            li.appendChild(a);
            return li;
        };

        this.paginationList.appendChild(createPageItem(this.options.lang.prev, this.currentPage - 1, 'previous'));

        let startPage = 1;
        let endPage = this.totalPages;
        
        if (this.totalPages > 10) {
             if (this.currentPage <= 6) {
                 endPage = 10;
             } else if (this.currentPage + 4 >= this.totalPages) {
                 startPage = this.totalPages - 9;
             } else {
                 startPage = this.currentPage - 5;
                 endPage = this.currentPage + 4;
             }
        }

        for (let i = startPage; i <= endPage; i++) {
            this.paginationList.appendChild(createPageItem(i, i, 'number'));
        }

        this.paginationList.appendChild(createPageItem(this.options.lang.next, this.currentPage + 1, 'next'));
        
        if (this.totalRows === 0) {
            this.footer.style.display = 'none';
        } else {
            this.footer.style.display = '';
        }
    }

    updateHeaderIndicators() {
        if (!this.headers) {
            return;
        }
        this.headers.forEach((th, index) => {
            th.classList.remove('simple-table-sort-asc', 'simple-table-sort-desc');
            if (this.order && this.order[0] === index) {
                th.classList.add(
                    this.order[1] === 'desc' ? 'simple-table-sort-desc' : 'simple-table-sort-asc'
                );
            }
        });
    }
}

export const initSimpleTables = () => {
    if (typeof document === 'undefined') {
        return;
    }
    document.querySelectorAll('[data-simple-table]').forEach((table) => {
        if (table.__voyagerSimpleTable) {
            return;
        }
        try {
            const rawConfig = table.getAttribute('data-simple-table');
            const options = rawConfig ? JSON.parse(rawConfig) : {};
            table.__voyagerSimpleTable = new SimpleTable(table, options);
        } catch (error) {
            console.error('Voyager simple table init failed', error);
        }
    });
};