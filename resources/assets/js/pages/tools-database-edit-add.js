let componentsRegistered = false;
let mountedOnce = false;

const getToastr = () => window.toastr || (window.Voyager && window.Voyager.toastr) || null;

const parseJson = (elementId) => {
    if (typeof document === 'undefined') return null;
    const el = document.getElementById(elementId);
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || 'null');
    } catch (error) {
        console.error(`[VoyagerToolsDatabase] Failed to parse ${elementId}`, error);
        return null;
    }
};

const getTemplate = (elementId) => {
    if (typeof document === 'undefined') return '';
    const el = document.getElementById(elementId);
    if (!el) return '';
    if (el.tagName === 'SCRIPT') {
        return (el.textContent || '').trim();
    }
    return (el.innerHTML || '').trim();
};

const deepClone = (value) => {
    try {
        return JSON.parse(JSON.stringify(value));
    } catch {
        return value;
    }
};

const getDbTypeResolver = (typesData, { unknownTypeMessage } = {}) => {
    const dbTypes = typesData || {};
    return (name) => {
        let type;
        const normalized = (name || '').toString().toLowerCase().trim();
        for (const category in dbTypes) {
            if (!Object.prototype.hasOwnProperty.call(dbTypes, category)) continue;
            const found = (dbTypes[category] || []).find((candidate) => normalized === (candidate.name || '').toLowerCase());
            if (found) {
                type = found;
                break;
            }
        }

        if (type) return type;

        const toastr = getToastr();
        toastr && toastr.error((unknownTypeMessage || 'Unknown type') + ': ' + normalized);

        const fallback = dbTypes && dbTypes.Numbers && dbTypes.Numbers[0];
        return fallback || { name: 'integer', default: {} };
    };
};

const registerComponents = (Vue, config) => {
    if (componentsRegistered) return;

    const typesData = parseJson('voyager-db-types-data') || {};
    const getDbType = getDbTypeResolver(typesData, {
        unknownTypeMessage: config.i18n && config.i18n.unknownType ? config.i18n.unknownType : 'Unknown type',
    });

    const databaseTypesTemplate = getTemplate('voyager-db-types-template');
    const databaseColumnDefaultTemplate = getTemplate('voyager-db-column-default-template');
    const databaseTableHelperButtonsTemplate = getTemplate('voyager-db-table-helper-buttons-template');
    const databaseColumnTemplate = getTemplate('voyager-db-column-template');
    const databaseTableEditorTemplate = getTemplate('voyager-db-table-editor-template');

    Vue.registerComponent('database-types', {
        props: {
            column: { type: Object, required: true }
        },
        data() {
            return {
                dbTypes: typesData
            };
        },
        template: databaseTypesTemplate,
        methods: {
            onTypeChange(event) {
                this.$emit('typeChanged', this.getType(event.target.value));
            },
            getType(name) {
                return getDbType(name);
            }
        }
    });

    const defaultOptions = {
        type: 'text',
        step: false,
        min: false,
        max: false,
        class: false,
        disabled: false
    };

    Vue.registerComponent('database-column-default', {
        props: {
            column: { type: Object, required: true }
        },
        template: databaseColumnDefaultTemplate,
        methods: {
            onDefaultInput(event) {
                let defaultValue = (event.target.value || '').trim();
                if (defaultValue === '') {
                    defaultValue = null;
                }
                this.column.default = defaultValue;
            },
            getOption(option) {
                if (this.column.type && this.column.type.default && this.column.type.default[option]) {
                    return this.column.type.default[option];
                }
                return false;
            },
            getType() {
                const type = this.getOption('type');
                return type || 'text';
            }
        },
        computed: {
            options() {
                if (!this.column.type || !this.column.type.default) {
                    return defaultOptions;
                }
                return {
                    type: this.getType(),
                    step: this.getOption('step'),
                    min: this.getOption('min'),
                    max: this.getOption('max'),
                    class: this.getOption('class'),
                    disabled: this.getOption('disabled')
                };
            }
        }
    });

    Vue.registerComponent('database-table-helper-buttons', {
        template: databaseTableHelperButtonsTemplate,
        methods: {
            addColumn(column) {
                this.$emit('columnAdded', column);
            },
            makeColumn(options) {
                return Object.assign(
                    {
                        name: '',
                        oldName: '',
                        type: getDbType('integer'),
                        length: null,
                        fixed: false,
                        unsigned: false,
                        autoincrement: false,
                        notnull: false,
                        default: null
                    },
                    options
                );
            },
            addNewColumn() {
                this.addColumn(this.makeColumn());
            },
            addTimestamps() {
                this.addColumn(
                    this.makeColumn({
                        name: 'created_at',
                        type: getDbType('timestamp')
                    })
                );
                this.addColumn(
                    this.makeColumn({
                        name: 'updated_at',
                        type: getDbType('timestamp')
                    })
                );
            },
            addSoftDeletes() {
                this.addColumn(
                    this.makeColumn({
                        name: 'deleted_at',
                        type: getDbType('timestamp')
                    })
                );
            }
        }
    });

    Vue.registerComponent('database-column', {
        data() {
            return {
                lengthInputType: 'number'
            };
        },
        props: {
            column: { type: Object, required: true },
            index: { type: Object, required: true }
        },
        template: databaseColumnTemplate,
        methods: {
            deleteColumn() {
                this.$emit('columnDeleted', this.column);
            },
            onColumnNameInput(event) {
                const newName = event.target.value;
                this.$emit('columnNameUpdated', {
                    column: this.column,
                    newName: newName
                });
            },
            onColumnTypeChange(type) {
                if (type.notSupportIndex && this.index.type) {
                    this.$emit('indexDeleted', this.index);
                }
                this.column.default = null;
                this.column.type = type;
                this.setLengthInputType();
            },
            onIndexTypeChange(event) {
                if (this.column.name == '') {
                    const toastr = getToastr();
                    toastr && toastr.error(config.i18n && config.i18n.nameWarning ? config.i18n.nameWarning : '');
                    return;
                }
                this.$emit('indexChanged', {
                    columns: [this.column.name],
                    old: this.index,
                    newType: event.target.value
                });
            },
            setLengthInputType() {
                const name = this.column.type && this.column.type.name;
                if (name == 'double' || name == 'float' || name == 'decimal') {
                    this.lengthInputType = 'text';
                } else {
                    this.lengthInputType = 'number';
                }
            }
        },
        mounted() {
            this.setLengthInputType();
        }
    });

    Vue.registerComponent('database-table-editor', {
        props: {
            table: { type: Object, required: true }
        },
        data() {
            return {
                emptyIndex: { type: '', name: '' },
                compositeIndexes: []
            };
        },
        template: databaseTableEditorTemplate,
        mounted() {
            this.compositeIndexes = this.getCompositeIndexes();
            const compositeColumns = this.getIndexesColumns(this.compositeIndexes);
            for (const col in compositeColumns) {
                const column = this.getColumn(compositeColumns[col]);
                if (column) {
                    column.composite = true;
                }
            }
        },
        computed: {
            tableHasColumns() {
                return this.table.columns.length;
            }
        },
        methods: {
            addColumn(column) {
                column.name = column.name.trim();
                if (column.name && this.hasColumn(column.name)) {
                    const toastr = getToastr();
                    const message = (config.i18n && config.i18n.columnAlreadyExists)
                        ? config.i18n.columnAlreadyExists.replace('__name', column.name)
                        : `Column ${column.name} already exists`;
                    toastr && toastr.error(message);
                    return;
                }
                this.table.columns.push(deepClone(column));
            },
            getColumn(name) {
                const normalized = (name || '').toString().toLowerCase().trim();
                return this.table.columns.find(function (column) {
                    return normalized == (column.name || '').toLowerCase();
                });
            },
            hasColumn(name) {
                return !!this.getColumn(name);
            },
            renameColumn(column) {
                let newName = (column.newName || '').trim();
                const target = column.column;
                let existingColumn;
                if ((existingColumn = this.getColumn(newName)) && existingColumn !== target) {
                    const toastr = getToastr();
                    const message = (config.i18n && config.i18n.columnAlreadyExists)
                        ? config.i18n.columnAlreadyExists.replace('__name', newName)
                        : `Column ${newName} already exists`;
                    toastr && toastr.error(message);
                    return;
                }
                const index = this.getColumnsIndex(target.name);
                if (index !== this.emptyIndex) {
                    index.columns = [newName];
                }
                target.name = newName;
            },
            deleteColumn(column) {
                const columnPos = this.table.columns.indexOf(column);
                if (columnPos !== -1) {
                    this.table.columns.splice(columnPos, 1);
                    this.deleteIndex(this.getColumnsIndex(column.name));
                }
            },
            columnsMatch(first, second) {
                const normalizedFirst = Array.isArray(first) ? [...first] : [];
                const normalizedSecond = Array.isArray(second) ? [...second] : [];
                if (normalizedFirst.length !== normalizedSecond.length) {
                    return false;
                }
                const sortedFirst = normalizedFirst.slice().sort();
                const sortedSecond = normalizedSecond.slice().sort();
                return sortedFirst.every((value, index) => value === sortedSecond[index]);
            },
            getColumnsIndex(columns) {
                if (!Array.isArray(columns)) {
                    columns = [columns];
                }
                let index = null;
                for (const i in this.table.indexes) {
                    if (this.columnsMatch(this.table.indexes[i].columns, columns)) {
                        index = this.table.indexes[i];
                        break;
                    }
                }
                if (!index) {
                    index = this.emptyIndex;
                }
                index.table = this.table.name;
                return index;
            },
            onIndexChange(index) {
                if (index.old === this.emptyIndex) {
                    return this.addIndex({
                        columns: index.columns,
                        type: index.newType
                    });
                }
                if (index.newType == '') {
                    return this.deleteIndex(index.old);
                }
                return this.updateIndex(index.old, index.newType);
            },
            addIndex(index) {
                if (index.type == 'PRIMARY') {
                    if (this.table.primaryKeyName) {
                        const toastr = getToastr();
                        toastr && toastr.error(config.i18n && config.i18n.tableHasIndex ? config.i18n.tableHasIndex : '');
                        return;
                    }
                    this.table.primaryKeyName = 'primary';
                }
                this.setIndexName(index);
                this.table.indexes.push(index);
            },
            deleteIndex(index) {
                const indexPos = this.table.indexes.indexOf(index);
                if (indexPos !== -1) {
                    if (index.type == 'PRIMARY') {
                        this.table.primaryKeyName = false;
                    }
                    this.table.indexes.splice(indexPos, 1);
                }
            },
            updateIndex(index, newType) {
                if (index.type == 'PRIMARY') {
                    this.table.primaryKeyName = false;
                } else if (newType == 'PRIMARY') {
                    if (this.table.primaryKeyName) {
                        const toastr = getToastr();
                        toastr && toastr.error(config.i18n && config.i18n.tableHasIndex ? config.i18n.tableHasIndex : '');
                        return;
                    }
                    this.table.primaryKeyName = 'primary';
                }
                index.type = newType;
                this.setIndexName(index);
            },
            setIndexName(index) {
                if (index.type == 'PRIMARY') {
                    index.name = 'primary';
                } else {
                    index.name = '';
                }
            },
            getCompositeIndexes() {
                const composite = [];
                for (const i in this.table.indexes) {
                    if (this.table.indexes[i].isComposite) {
                        composite.push(this.table.indexes[i]);
                    }
                }
                return composite;
            },
            getIndexesColumns(indexes) {
                const columns = [];
                for (const i in indexes) {
                    for (const col in indexes[i].columns) {
                        columns.push(indexes[i].columns[col]);
                    }
                }
                return [...new Set(columns)];
            }
        }
    });

    componentsRegistered = true;
};

export const initToolsDatabaseEditAdd = () => {
    if (typeof document === 'undefined') return;
    const root = document.getElementById('dbManager');
    if (!root) return;

    const config = parseJson('voyager-tools-database-edit-add-config');
    if (!config) return;

    if (mountedOnce) return;
    mountedOnce = true;

    if (!window.Voyager || typeof window.Voyager.withVue !== 'function') {
        return;
    }

    window.Voyager.withVue(function (Vue) {
        registerComponents(Vue, config);
        Vue.createApp({
            data() {
                return {
                    table: {},
                    originalTable: config.originalTable,
                    oldTable: config.oldTable,
                    tableJson: ''
                };
            },
            created() {
                if (this.oldTable) {
                    this.table = this.oldTable;
                } else {
                    this.table = deepClone(this.originalTable);
                }
            },
            methods: {
                stringifyTable() {
                    this.tableJson = JSON.stringify(this.table);
                    this.$nextTick(() => this.$refs.form.submit());
                }
            }
        }).mount('#dbManager');
    });
};

export const subscribeToEvents = (events) => {
    if (!events || typeof events.on !== 'function') return;
    events.on('dom:updated', () => initToolsDatabaseEditAdd());
};
