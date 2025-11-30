const DEFAULT_OPTIONS = {
    perPage: 25,
    search: true,
    searchPlaceholder: 'Search...',
    order: null
};

const UNSORTABLE_CLASSES = ['actions', 'no-sort', 'dt-not-orderable', 'js-no-sort'];

export default class SimpleTable {
    constructor(table, options = {}) {
        this.table = table;
        this.tbody = table.tBodies[0];
        if (!this.tbody) {
            return;
        }
        this.options = Object.assign({}, DEFAULT_OPTIONS, options);
        this.options.perPage = parseInt(this.options.perPage, 10);
        if (isNaN(this.options.perPage) || this.options.perPage < 0) {
            this.options.perPage = 0;
        }
        this.rows = Array.from(this.tbody.rows);
        this.currentPage = 1;
        this.searchTerm = '';
        this.order = this.normalizeOrder(this.options.order);

        this.buildControls();
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

    buildControls() {
        this.controlsWrapper = document.createElement('div');
        this.controlsWrapper.className = 'simple-table-controls';

        if (this.options.search !== false) {
            const searchWrapper = document.createElement('div');
            searchWrapper.className = 'simple-table-search form-group';

            this.searchInput = document.createElement('input');
            this.searchInput.type = 'search';
            this.searchInput.className = 'form-control';
            this.searchInput.placeholder = this.options.searchPlaceholder;
            this.searchInput.addEventListener('input', () => {
                this.searchTerm = this.searchInput.value.toLowerCase();
                this.currentPage = 1;
                this.render();
            });

            searchWrapper.appendChild(this.searchInput);
            this.controlsWrapper.appendChild(searchWrapper);
        }

        this.infoElement = document.createElement('div');
        this.infoElement.className = 'simple-table-info text-muted';
        this.controlsWrapper.appendChild(this.infoElement);

        if (this.options.perPage > 0) {
            const paginationWrapper = document.createElement('div');
            paginationWrapper.className = 'simple-table-pagination btn-group';

            this.prevButton = document.createElement('button');
            this.prevButton.type = 'button';
            this.prevButton.className = 'btn btn-sm btn-default';
            this.prevButton.innerText = '‹';
            this.prevButton.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage -= 1;
                    this.render();
                }
            });

            this.nextButton = document.createElement('button');
            this.nextButton.type = 'button';
            this.nextButton.className = 'btn btn-sm btn-default';
            this.nextButton.innerText = '›';
            this.nextButton.addEventListener('click', () => {
                if (this.currentPage < this.totalPages) {
                    this.currentPage += 1;
                    this.render();
                }
            });

            paginationWrapper.appendChild(this.prevButton);
            paginationWrapper.appendChild(this.nextButton);
            this.controlsWrapper.appendChild(paginationWrapper);
        }

        this.table.parentNode.insertBefore(this.controlsWrapper, this.table);
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
            this.infoElement.innerText = '0 results';
            return;
        }

        if (this.options.perPage > 0) {
            const start = (this.currentPage - 1) * this.options.perPage + 1;
            const end = Math.min(this.currentPage * this.options.perPage, this.totalRows);
            this.infoElement.innerText = `Showing ${start}-${end} of ${this.totalRows}`;
        } else {
            this.infoElement.innerText = `Showing ${this.totalRows} entries`;
        }
    }

    updatePagination() {
        if (!this.prevButton || !this.nextButton) {
            return;
        }
        this.prevButton.disabled = this.currentPage <= 1;
        this.nextButton.disabled = this.currentPage >= this.totalPages;
        if (this.totalRows <= this.options.perPage) {
            this.prevButton.parentElement.style.display = 'none';
        } else {
            this.prevButton.parentElement.style.display = '';
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
