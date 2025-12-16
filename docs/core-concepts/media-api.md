# Media API (this fork)

This fork exposes a small admin-only JSON API for the custom Media Storage subsystem.
It is used by advanced formfields (`adv_image`, `adv_media_files`, `adv_inline_set`) and can be reused by custom fields.

All routes are registered in `routes/voyager.php` under the `voyager.media-api.*` group and are protected by the admin middleware.

## Base URL

Voyager admin routes are mounted under `config('voyager.path')` (usually `admin`), so the URLs below assume:

`/{adminPath}/api/media/...`

If possible, prefer Laravel route names instead of hardcoding paths:

```php
route('voyager.media-api.show', ['media' => $id]);
```

## Common requirements

- Authenticated admin session (same as Voyager admin panel)
- CSRF token for `POST`/`DELETE` requests
- `Accept: application/json`

In Blade you usually already have the CSRF token meta tag:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

## Endpoints

### Upload media

`POST /{adminPath}/api/media/upload` (`voyager.media-api.upload`)

Request: `multipart/form-data`

- `file` (required) — uploaded file
- `model_type` (required) — fully qualified model class, e.g. `App\\Models\\Post`
- `model_id` (required) — model primary key
- `collection_name` (optional) — defaults to `default`

Response (`200`):

```json
{
  "status": "success",
  "media": { "id": 123, "model_type": "...", "model_id": 1, "collection_name": "default", "...": "..." }
}
```

Errors:
- `400` invalid model class
- `403` not authorized to `edit` the model
- `422` validation errors

Example (fetch):

```js
const token = document.querySelector('meta[name="csrf-token"]').content;
const form = new FormData();
form.append('file', fileInput.files[0]);
form.append('model_type', 'App\\\\Models\\\\Post');
form.append('model_id', '10');
form.append('collection_name', 'gallery');

const res = await fetch('/admin/api/media/upload', {
  method: 'POST',
  headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
  body: form,
});
```

### Show media (includes props + URL helpers)

`GET /{adminPath}/api/media/{media}` (`voyager.media-api.show`)

Response (`200`):

```json
{
  "status": "success",
  "media": {
    "id": 123,
    "props": { "title": "Cover", "alt": "..." },
    "url": "/storage/posts/media/2025/12/cover.jpg",
    "full_url": "https://example.com/storage/posts/media/2025/12/cover.jpg"
  }
}
```

Authorization:
- `edit` on the owning model (if present)

### Delete media

`DELETE /{adminPath}/api/media/{media}` (`voyager.media-api.delete`)

Response (`200`):

```json
{ "status": "success", "message": "Media deleted successfully" }
```

Authorization:
- `delete` on the owning model (if present)

### Update media props

`POST /{adminPath}/api/media/{media}/props` (`voyager.media-api.update-props`)

Request: JSON or form-encoded

- `props` (optional) — object/array, stored as JSON

Response (`200`):

```json
{ "status": "success", "media": { "id": 123, "props": { "...": "..." } } }
```

Notes:
- Response JSON is encoded with `JSON_INVALID_UTF8_SUBSTITUTE` (if available) to avoid crashing on malformed UTF-8.

Example (fetch):

```js
await fetch(`/admin/api/media/${mediaId}/props`, {
  method: 'POST',
  headers: {
    'X-CSRF-TOKEN': token,
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ props: { title: 'New title' } }),
});
```

### Reorder media within a collection

`POST /{adminPath}/api/media/reorder` (`voyager.media-api.reorder`)

Request: JSON or form-encoded

- `model_type` (required) — fully qualified model class
- `model_id` (required) — model primary key
- `collection_name` (required)
- `order` (required) — array of media IDs in the desired order

Response (`200`):

```json
{ "status": "success", "message": "Media reordered successfully" }
```

### Crop an image (and optionally downscale)

`POST /{adminPath}/api/media/{media}/crop` (`voyager.media-api.crop`)

Only works for image media (`mime_type` must start with `image/`).

Request: JSON or form-encoded

- `x`, `y` (required) — crop origin (pixels)
- `width`, `height` (required) — crop size (pixels)
- `max_width` (optional) — if set and the cropped image is wider, it is downscaled (keeps aspect ratio)
- `max_height` (optional) — if set and the cropped image is taller, it is downscaled (keeps aspect ratio)

Response (`200`):

```json
{ "status": "success", "message": "Image cropped successfully." }
```

Errors:
- `400` when media is not an image
- `422` validation errors

After a successful crop, you usually need to refresh the UI preview (cache-bust by adding `?t=<updated_at>` or re-fetch `GET /api/media/{id}`).

