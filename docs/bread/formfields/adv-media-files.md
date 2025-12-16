# Advanced Media Files (`adv_media_files`)

`adv_media_files` is a gallery/collection field built on top of Voyager's Media Storage subsystem (polymorphic `media` table). It supports uploading multiple files, reordering, bulk selection, replacing a file, editing props, and cropping images.

This field is designed as a "power gallery" for admins:
- supports mixed file types (images + documents)
- shows file-type icons for non-images
- persists ordering immediately on drag-and-drop
- stores additional metadata per media item in `media.props`

## Details JSON

```json
{
  "collection_name": "files",
  "input_accept": "image/*,.pdf,.zip",
  "extra_fields": {
    "subtitle": { "type": "text", "title": "Subtitle" },
    "content":  { "type": "ace",  "title": "Content", "class": "col-md-12" },
    "link":     { "type": "text", "title": "Link" }
  }
}
```

### Options

- `collection_name` (string, optional): media collection name (defaults to the field name).
- `input_accept` (string, optional): forwarded to the `<input type="file" accept="...">`.
- `extra_fields` (object, optional): additional props shown in the "Edit meta" modal and stored in `media.props`.
  - Supported `type`: `text`, `textarea`, `ace`.
  - `ace` uses HTML mode + `monokai` theme by default.
  - `class` (optional): extra CSS class for the wrapper (`col-md-*` etc).

## Behaviour

- Reorder is saved immediately on drag-and-drop (server-side update of `media.order`).
- Replacing a file keeps the same media record (updates file/path/mime/size).
- Cropping is available for image items only.

## Stored data

Each uploaded file becomes a row in the `media` table:

- `model_type/model_id` → your model
- `collection_name` → from details (`collection_name`) or the field name
- `order` → list position
- `props` → `title`, `alt`, and keys from `extra_fields`

## Crop

The crop modal supports:
- aspect ratio presets (including free crop)
- max width / max height constraints (downscale after crop)

Crop is performed server-side via the media API endpoint and the preview is refreshed using cache-busting.

## Notes

- If you change `collection_name` later, existing media items will not move automatically; treat it as part of your data contract.
- For JSON in `props`, always store arrays/objects; avoid invalid UTF-8 (the backend tolerates it, but it should not be a normal workflow).

## Example: field that accepts mixed files + extra meta

```json
{
  "collection_name": "attachments",
  "input_accept": "image/*,.pdf,.zip,.doc,.docx,.xls,.xlsx",
  "extra_fields": {
    "subtitle": { "type": "text", "title": "Subtitle" },
    "content": { "type": "ace", "title": "HTML snippet", "class": "col-md-12" },
    "link": { "type": "text", "title": "Link" }
  }
}
```

Stored values:
- Each file is a `media` row with `collection_name="attachments"`.
- Extra fields are stored in `media.props` (JSON) under the same keys.
