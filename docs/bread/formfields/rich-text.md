# Rich Text (Jodit)

Voyager ships the [`rich_text_box`](../../bread/fields.md#rich_text_box) field with [Jodit Editor](https://xdsoft.net/jodit/).  
The editor is lazy-loaded through `js/editors.js`, so it only loads when a page contains a rich text area.

On load, Voyager will call `Voyager.loadEditors()` and then initialize Jodit for every textarea with the `richTextBox` class.
You can customize the initialization by calling the exported `initJodit(selector, options)` function yourself.

## Customizing the default options

The BREAD "Details" JSON (`Tools → BREAD → Edit BREAD`) is passed directly into the field template as `$options`.
You can override any of the defaults by defining an `options` object:

```json
{
    "options": {
        "language": "ru",
        "buttons": [
            "bold", "italic", "underline",
            "|", "ul", "ol", "|", "image", "link", "|", "source"
        ]
    }
}
```

Voyager will merge the properties above with the safe defaults (`type_slug`, `upload_url`, Ace settings, etc.) before initializing Jodit.

## Hooking into initialization

If you need to adjust behaviour before the editors are mounted (for example for all rich text fields),
call `Voyager.loadEditors()` and then `initJodit` yourself:

```blade
@push('javascript')
<script type="module">
Voyager.loadEditors().then(({ initJodit }) => {
    initJodit('textarea.richTextBox', {
        toolbarAdaptive: true,
        buttons: [
            'bold', 'italic', 'underline', '|',
            'ul', 'ol', '|',
            'image', 'video', 'table', '|',
            'source'
        ]
    });
});
</script>
@endpush
```

> **Note:** `initJodit` can be called multiple times with different selectors if you need per-field overrides.
> The helper checks for already-initialized instances and will skip them automatically.
