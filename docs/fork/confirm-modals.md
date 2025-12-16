# Confirm Modals (unified component)

The admin UI uses a unified confirm modal to avoid duplicated markup/scripts and inconsistent Bootstrap backdrop handling.

## Component

Blade component:
- `voyager::components.modal-confirm`

Typical usage:

```blade
@include('voyager::components.modal-confirm', [
    'id' => 'delete-modal',
    'title' => __('voyager::generic.are_you_sure_delete'),
    'message' => __('voyager::generic.delete_confirm'),
    'confirmText' => __('voyager::generic.delete_confirm'),
    'confirmClass' => 'btn-danger',
    'confirmButtonClass' => 'js-confirm-delete',
    'icon' => 'voyager-trash'
])
```

## JavaScript helper

The modal is wired through a small native JS helper (no jQuery dependency), which:
- shows/hides modals via the Voyager Bootstrap compatibility layer
- prevents “stuck backdrop” issues
- supports reusing the same modal for multiple actions by rebinding the confirm handler

If you implement a new destructive action, prefer this modal instead of `window.confirm(...)`.

