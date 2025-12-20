<template id="voyager-db-table-helper-buttons-template">
    <div>
        <div class="btn btn-success" @click="addNewColumn">+ {{ __('voyager::database.add_new_column') }}</div>
        <div class="btn btn-success" @click="addTimestamps">+ {{ __('voyager::database.add_timestamps') }}</div>
        <div class="btn btn-success" @click="addSoftDeletes">+ {{ __('voyager::database.add_softdeletes') }}</div>
    </div>
</template>

