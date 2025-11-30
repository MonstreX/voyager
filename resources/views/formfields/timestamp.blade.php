<div class="alert alert-warning">
    Date/time picker widgets have been removed. Please enter the timestamp manually using your browser's date/time field.
</div>
<input @if($row->required == 1) required @endif type="datetime-local" class="form-control" name="{{ $row->field }}"
       value="@if(isset($dataTypeContent->{$row->field})){{ \Carbon\Carbon::parse(old($row->field, $dataTypeContent->{$row->field}))->format('Y-m-d\\TH:i') }}@else{{old($row->field)}}@endif">
