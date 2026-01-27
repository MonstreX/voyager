# Fork overview (Voyager package)

This repository is a fork of the classic TCG Voyager admin panel, updated for a modern Laravel stack and extended with additional features migrated from a legacy codebase (`voyager-extension`) and a custom Media Storage subsystem (instead of Spatie Media Library).

## What changed vs the original (Voyager ~1.7)

### Platform / dependencies

- Laravel compatibility: **modern Laravel (11/12)**.
- PHP compatibility: **PHP 8.2+**.

### Frontend tooling

- Build system: **Laravel Mix/Webpack** -> **Vite**.
- Admin assets are compiled into `publishable/assets` and must be published to the host app (see "Assets & Editors").

### JavaScript architecture

- Gradual removal of legacy jQuery patterns in favor of **native JavaScript modules**.
- Centralized compatibility layer for Bootstrap interactions (modals/dropdowns/collapse).
- Unified confirm modals to avoid duplicated markup/scripts across pages.

### Editors

- WYSIWYG: **TinyMCE** -> **Jodit** (lazy-loaded).
- Code editor: **Ace** (lazy-loaded; used by core `code_editor` and advanced fields).

### Media layer

- Replaced Spatie Media Library usage with a custom Media Storage:
  - Polymorphic `media` table (`model_type`, `model_id`)
  - `collection_name` + `order` + `props` JSON
  - Dated path strategy: `{table}/media/{Y}/{m}`
  - Media API endpoints used by advanced fields and Media Manager crop UI

## Major feature additions

### Advanced BREAD fields (`adv_*`)

- `adv_image` (single media item + props + crop)
- `adv_media_files` (gallery/collection: reorder, bulk, replace, props modal, crop)
- `adv_inline_set` (repeatable rows stored as JSON; supports Jodit/Ace/media)
- `adv_related` (autocomplete + Sortable ordering; stores compact JSON)
- `adv_select_dropdown_tree` (hierarchical belongsTo dropdown)
- `adv_json`, `adv_fields_group`

### Admin UX improvements

- Browse Filters on BREAD browse pages (session-based + URL params).
- Menu Item `status` toggle (affects menu tree and edit modal).
- Media Manager crop improvements (aspect ratios + max width/height + preview refresh).

For a chronological migration log see `VE-MIGRATION.md` (project root) and `ADMIN-REVIEW.md` (project root).
