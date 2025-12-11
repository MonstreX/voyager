@extends('voyager::master')

@php
    $isTree = false;
    if (isset($dataType) && isset($dataType->browseRows)) {
        foreach($dataType->browseRows as $row) {
            if ($row->field == 'parent_id' && isset($row->details->browse_tree) && $row->details->browse_tree) {
                $isTree = true;
                break;
            }
        }
    }
@endphp

@if($isTree)
    @include('voyager::bread.browse-tree')
@else
    @include('voyager::bread.browse-list')
@endif