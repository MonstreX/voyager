<script type="application/json" id="voyager-db-types-data">@json($db->types)</script>

<script type="text/x-template" id="voyager-db-types-template">
<div>
    <select :value="column.type.name" @change="onTypeChange" class="form-control voyager-select">
        <optgroup v-for="(types, category) in dbTypes" :label="category">
            <option v-for="type in types" :value="type.name" :disabled="type.notSupported">
                @{{ type.name.toUpperCase() }}
            </option>
        </optgroup>
    </select>
    <div v-if="column.type.notSupported">
        <small>{{ __('voyager::database.type_not_supported') }}</small>
    </div>
</div>
</script>
