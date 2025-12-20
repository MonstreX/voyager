@include('voyager::tools.database.vue-components.database-types')
@include('voyager::tools.database.vue-components.database-column-default')

<template id="voyager-db-column-template">
    <tr class="newTableRow">
        <td>
            <input :value="column.name" @input="onColumnNameInput" type="text" class="form-control" required pattern="{{ $db->identifierRegex }}">
        </td>

        <td>
            <database-types
                :column="column"
                @typeChanged="onColumnTypeChange">
            </database-types>
        </td>

        <td>
            <input v-model.number="column.length" :type="lengthInputType" min="0" class="form-control">
        </td>

        <td>
            <input v-model="column.notnull" type="checkbox">
        </td>

        <td>
            <input v-model="column.unsigned" type="checkbox">
        </td>

        <td>
            <input v-model="column.autoincrement" type="checkbox">
        </td>

        <td>
            <select :value="index.type" @change="onIndexTypeChange"
                    :disabled="column.type.notSupportIndex"
                    class="form-control voyager-select">
                <option value=""></option>
                <option value="INDEX">{{ __('voyager::database.index') }}</option>
                <option value="UNIQUE">{{ __('voyager::database.unique') }}</option>
                <option value="PRIMARY">{{ __('voyager::database.primary') }}</option>
            </select>
            <small v-if="column.composite" v-once>{{ __('voyager::database.composite_warning') }}</small>
        </td>

        <td>
            <database-column-default :column="column"></database-column-default>
        </td>

        <td>
            <div class="btn btn-danger delete-row" @click="deleteColumn"><i class="voyager-trash"></i></div>
        </td>

    </tr>
</template>

