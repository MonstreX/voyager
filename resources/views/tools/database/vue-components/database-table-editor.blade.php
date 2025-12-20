@include('voyager::tools.database.vue-components.database-column')
@include('voyager::tools.database.vue-components.database-table-helper-buttons')

<script type="text/x-template" id="voyager-db-table-editor-template">
<div class="panel panel-bordered">
    <div class="panel-body">
        <div class="row mb-30">
        @if($db->action == 'update')
            <div class="col-md-12">
        @else
            <div class="col-md-6">
        @endif
                <label for="name">{{ __('voyager::database.table_name') }}</label><br>
                <input v-model.trim="table.name" type="text" class="form-control" placeholder="{{ __('voyager::database.table_name') }}" required pattern="{{ $db->identifierRegex }}">
            </div>

        @if($db->action == 'create')
            <div class="col-md-3 col-sm-4 col-xs-6">
                <label for="create_model">{{ __('voyager::database.create_model_table') }}</label><br>
                <input type="checkbox" name="create_model" data-toggle="toggle"
                       data-on="{{ __('voyager::generic.yes_please') }}" data-off="{{ __('voyager::generic.no_thanks') }}">
            </div>
            {{--
                Hide migration button until feature is available.
                 <div class="col-md-3 col-sm-4 col-xs-6">
                    <label for="create_migration">{{ __('voyager::database.create_migration') }}</label><br>
                    <input disabled type="checkbox" name="create_migration" data-toggle="toggle"
                           data-on="{{ __('voyager::generic.yes_please') }}" data-off="{{ __('voyager::generic.no_thanks') }}">
                </div>
            --}}
        @endif
        </div><!-- .panel-body .row -->

        <div v-if="compositeIndexes.length" v-once class="alert alert-danger">
            <p>{{ __('voyager::database.no_composites_warning') }}</p>
        </div>

        <div id="alertsContainer"></div>

        <template v-if="tableHasColumns">
            <p>{{ __('voyager::database.table_columns') }}</p>

            <table class="table table-bordered" style="width:100%;">
                <thead>
                <tr>
                    <th>{{ __('voyager::generic.name') }}</th>
                    <th>{{ __('voyager::generic.type') }}</th>
                    <th>{{ __('voyager::generic.length') }}</th>
                    <th>{{ __('voyager::generic.not_null') }}</th>
                    <th>{{ __('voyager::generic.unsigned') }}</th>
                    <th>{{ __('voyager::generic.auto_increment') }}</th>
                    <th>{{ __('voyager::generic.index') }}</th>
                    <th>{{ __('voyager::generic.default') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                    <database-column
                        v-for="(column, index) in table.columns"
                        :column="column"
                        :index="getColumnsIndex(column.name)"
                        :key="index"
                        @columnNameUpdated="renameColumn"
                        @columnDeleted="deleteColumn"
                        @indexAdded="addIndex"
                        @indexDeleted="deleteIndex"
                        @indexUpdated="updateIndex"
                        @indexChanged="onIndexChange"
                    ></database-column>
                </tbody>
            </table>
        </template>
        <div v-else>
          <p>{{ __('voyager::database.table_no_columns') }}</p>
        </div>

        <div class="table-footer-actions">
            <database-table-helper-buttons
                @columnAdded="addColumn"
            ></database-table-helper-buttons>
        </div>
    </div><!-- .panel-body -->

    <div class="panel-footer">
        <input type="submit" class="btn btn-primary pull-right"
               value="@if($db->action == 'update'){{ __('voyager::database.update_table') }}@else{{ __('voyager::database.create_new_table') }}@endif"
               :disabled="!tableHasColumns">
        <div style="clear:both"></div>
    </div>
</div><!-- .panel -->
</script>
