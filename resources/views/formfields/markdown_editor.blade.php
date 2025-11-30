<div class="alert alert-warning">
    Markdown editor is temporarily disabled while we migrate away from EasyMDE. Please edit the raw markdown below.
 </div>
<textarea class="form-control easymde" name="{{ $row->field }}" id="markdown{{ $row->field }}">{{ old($row->field, $dataTypeContent->{$row->field} ?? '') }}</textarea>
