# Media Storage & API (custom subsystem)

This fork ships a custom Media Storage subsystem used by:
- `adv_image`
- `adv_media_files`
- `adv_inline_set`
- Media Manager crop UI

It replaces the Spatie Media Library dependency used in the legacy `voyager-extension` codebase.

For the full developer guide see: `../core-concepts/media-storage.md`.

## Data model: `media` table

Media is stored in a polymorphic table (`model_type`, `model_id`) and grouped into collections.

Common columns:
- `model_type` / `model_id`: polymorphic owner model
- `collection_name`: logical grouping per model (e.g. `cover`, `images`, `gallery`)
- `file_name`: original filename
- `path`: storage path (relative to disk root)
- `disk`: filesystem disk (`config('voyager.storage.disk')`)
- `mime_type`, `size`
- `order`: integer ordering within the collection
- `props`: JSON for extra metadata (title/alt/extra fields)

## Path strategy

The default path generator uses a dated strategy:

`{table}/media/{Y}/{m}/{slugged_name}.{ext}`

Implementation: `TCG\\Voyager\\Services\\Media\\MediaPathGenerator`.

## Service layer

### `MediaService`

Primary API for creating/updating/deleting media:
- `createFromFile($model, $file, $collectionName, $disk = null, array $meta = [])`
- `replaceMediaFile(Media $media, $file, array $meta = [])`
- `deleteMedia(Media $media)` (deletes file + DB row)
- `updateMediaProps(Media $media, array $props)`
- `reorderCollection($model, $collectionName, array $order)`

### `MediaUploader`

A fluent helper used by crop/resize flows:
- accepts either `UploadedFile` or raw bytes string
- supports `crop(...)` and `resize(...)` via the internal image processor
- finally `save()` delegates to `MediaService`

### `BreadFieldUploadService`

Post-save handler used by BREAD controllers to keep controllers small:
- handles uploads/props for `adv_image`, `adv_media_files`, `adv_inline_set`
- required for create-flow cases where `model_id` is available only after first `save()`

## Security notes (server-side)

- Media delete operations validate that the media belongs to the current model.
- For `adv_inline_set`, per-row deleted IDs are additionally restricted by `collection_name`.

## Media API endpoints (admin)

The exact routes are registered under `routes/voyager.php` and are used by the advanced fields and Media Manager UI.

Typical actions:
- `POST /admin/api/media/upload`
- `GET /admin/api/media/{id}`
- `DELETE /admin/api/media/{id}`
- `POST /admin/api/media/{id}/props`
- `POST /admin/api/media/{id}/crop`
- `POST /admin/api/media/reorder`

If you extend advanced fields or build a new one, prefer calling these endpoints (or `MediaService`) instead of duplicating storage logic.

Full reference: `../core-concepts/media-api.md`.
