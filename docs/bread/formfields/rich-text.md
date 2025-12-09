# Rich Text (Jodit)

Voyager ships the [`rich_text_box`](../../bread/fields.md#rich_text_box) field with [Jodit Editor](https://xdsoft.net/jodit/).  
The editor is lazy-loaded through `js/editors.js`, so it only loads when a page contains a rich text area.

On load, Voyager will call the global helper `window.VoyagerInitJodit(selector, options)` for every textarea with the `richTextBox` class.
This function accepts a CSS selector and a plain object with any Jodit configuration you would like to override.

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

## Hooking into the global helper

If you need to adjust behaviour before the editors are mounted (for example for all rich text fields),
listen for the `voyager:editors-ready` event and call `window.VoyagerInitJodit` yourself:

```blade
@push('javascript')
<script type="module">
document.addEventListener('voyager:editors-ready', () => {
    window.VoyagerInitJodit('textarea.richTextBox', {
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

> **Note:** `window.VoyagerInitJodit` can be called multiple times with different selectors if you need per-field overrides.
> The helper checks for already-initialized instances and will skip them automatically.
