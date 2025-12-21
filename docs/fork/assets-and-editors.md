# Assets & Editors (Vite, Jodit, Ace)

## Assets pipeline (Vite)

Voyager admin UI assets are built with Vite.

- Source: `resources/assets/js/*`, `resources/assets/sass/*`
- Output: `publishable/assets/*`
- Runtime location (host app): `public/vendor/voyager/*` (via `vendor:publish --tag=voyager_assets`)

If you change any admin JS/CSS in the package, rebuild and publish assets in the package repository:

```bash
npm install
npm run build
php artisan vendor:publish --tag=voyager_assets --force
```

## Lazy-loaded editors bundle

Jodit and Ace are shipped as a separate bundle (`js/editors.js`) and are loaded only when needed.

- Global loader: `window.Voyager.loadEditors()`
- Usage: `Voyager.loadEditors().then(({ initJodit, initAceEditors }) => { ... })`

## Jodit (rich text)

Used by:
- Core field: `rich_text_box`
- Advanced field: `adv_inline_set` (`type: richtext`)

Customization is documented in the Rich Text guide: `../bread/formfields/rich-text.md`.

## Ace (code editor)

Used by:
- Core field: `code_editor`
- Advanced fields:
  - `adv_inline_set` (`type: code`)
  - `adv_media_files` extra fields (`type: ace`)

Typical options:
- `data-theme="monokai"`
- `data-language="html"`
- `data-min-lines="3"`
- `data-max-lines="20"` (if provided)

Notes:
- Ace base path is published into `public/vendor/voyager/js/ace/libs`.
- To avoid slow DOM updates during drag-and-drop operations, some UIs hide `.ace_editor` while a Sortable item is being dragged.
