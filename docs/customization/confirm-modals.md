# Confirm Modals (this fork)

This fork replaces many duplicated legacy confirmation dialogs with a single reusable Blade component and a small native JavaScript helper.

The goal is to keep confirmation UX consistent across:
- BREAD browse bulk delete
- row delete buttons
- menu builder operations
- media manager operations
- `adv_image` / `adv_media_files` actions

## 1) Blade component

Use:

```blade
@include('voyager::components.modal-confirm', [
    'id' => 'my-confirm-modal',
    'title' => __('voyager::generic.are_you_sure'),
    'message' => __('voyager::generic.delete_confirm'),
    'confirmText' => __('voyager::generic.delete_confirm'),
    'confirmClass' => 'btn-danger',
    'confirmButtonClass' => 'my-confirm-button',
    'icon' => 'voyager-trash',
])
```

### Parameters

- `id` (string): modal element id
- `title` (string|html): modal title (rendered as HTML)
- `message` (string|html): modal body (rendered as HTML)
- `confirmText` / `cancelText` (string)
- `confirmClass` (string): appended to confirm button (`btn-danger` etc.)
- `confirmButtonClass` (string): extra class for wiring your own JS if needed
- `confirmButtonId` (string, optional)
- `icon` (string): icon class in title

> Note: `title` and `message` are rendered with `{!! !!}` to allow rich HTML.
> Do not pass untrusted user input directly.

## 2) JS helper: data-driven wiring

The helper module is loaded in `resources/assets/js/app.js` and listens for clicks on elements with `data-confirm-target`.

### Show a modal and perform a fetch request

```html
<button
  type="button"
  data-confirm-target="#my-confirm-modal"
  data-confirm-url="/admin/api/something/123"
  data-confirm-method="DELETE"
  data-csrf="...csrf-token..."
>
  Delete
</button>
```

When the confirm button is clicked, the module sends `fetch(confirmUrl, { method, headers })` and closes the modal.

### Show a modal and set a hidden form field

```html
<button
  type="button"
  data-confirm-target="#my-confirm-modal"
  data-confirm-field="my_field_clear"
  data-confirm-value="1"
>
  Clear
</button>

<input type="hidden" name="my_field_clear" value="0">
```

On confirm, the hidden input will be updated and the modal will be closed.

## 3) Extending behaviour

If you need custom behaviour (e.g. form submit, payload building, toastr), keep the component but attach your own handler to the confirm button using `confirmButtonClass` or `confirmButtonId`.

## 4) Implementation references

- Blade: `resources/views/components/modal-confirm.blade.php`
- JS: `resources/assets/js/modules/confirm-modal.js`
- Bootstrap compatibility: `resources/assets/js/core/bootstrap-compat.js`

