# Media Storage (this fork)

This fork replaces Spatie Media Library usage (from a legacy extension) with a small built-in Media Storage subsystem.

It is used by:
- `adv_image`
- `adv_media_files`
- `adv_inline_set`
- Media Manager (crop features and file operations)

## 1) Data model: `media` table

Media records are stored in a single table (`media`) and linked to Eloquent models via a polymorphic relation:

- `model_type` (string) — fully qualified class name
- `model_id` (int) — model primary key
- `collection_name` (string) — logical bucket within a model (`cover`, `gallery`, etc.)
- `order` (int) — ordering within a collection
- `props` (json/text) — arbitrary metadata (title, alt, extra fields)
- `disk` (string) — filesystem disk (defaults to `voyager.storage.disk`)
- `path` (string) — stored path on disk
- `file_name` (string) — original client file name
- `mime_type` (string)
- `size` (int)

## 2) Attaching Media to your model

Add the `HasMedia` trait to any model you want to store media for:

```php
use TCG\Voyager\Traits\HasMedia;

class Post extends Model
{
    use HasMedia;
}
```

### What the trait gives you

- `media()` morphMany relation
- `getMedia($collectionName = 'default')`
- `getFirstMedia($collectionName = 'default')`
- `getFirstMediaUrl($collectionName = 'default', $fallback = null)`
- `addMedia($file, $collectionName = 'default')` (returns `MediaUploadService`)

### Automatic cleanup on delete

`HasMedia` registers a model `deleting` listener and removes **all** related media records and files.

If your model uses soft deletes, this will also run on soft-delete (because Eloquent fires `deleting` on soft delete).
If you need different semantics (e.g. keep files on soft delete), override the model behaviour accordingly.

## 3) Uploading and managing media in code

### 3.1 Create a media record from an uploaded file

```php
$media = app(\TCG\Voyager\Services\Media\MediaService::class)
    ->createFromFile($post, $request->file('cover'), 'cover');
```

### 3.2 Fluent upload (MediaUploadService)

```php
$media = $post
    ->addMedia($request->file('cover'), 'cover')
    ->withProps([
        'title' => 'Cover',
        'alt'   => 'Cover image',
    ])
    ->save();
```

### 3.3 Update props

```php
app(\TCG\Voyager\Services\Media\MediaService::class)
    ->updateMediaProps($media, ['title' => 'New title']);
```

### 3.4 Replace the file but keep the same media record

```php
app(\TCG\Voyager\Services\Media\MediaService::class)
    ->replaceMediaFile($media, $request->file('replacement'));
```

### 3.5 Reorder a collection

```php
app(\TCG\Voyager\Services\Media\MediaService::class)
    ->reorderCollection($post, 'gallery', [12, 8, 15]);
```

## 4) Storage paths and file naming

By default, files are stored with the **DATED** strategy:

```
{table}/media/{Y}/{m}/{slugged_name}.{ext}
```

Examples:

- `posts/media/2025/12/cover.jpg`
- `pages/media/2025/12/header_2.webp`

Name collisions are resolved by appending an incrementing suffix.

### Custom path generator

`MediaService` reads an optional config key:

```php
config('voyager.media.path_generator', \TCG\Voyager\Services\Media\PathGeneratorService::class)
```

To override:

```php
// config/voyager.php
'media' => [
    'path_generator' => App\\Services\\MyPathGenerator::class,
],
```

Your generator must implement a static `generate(array $options): string` method.

## 5) Media props and UTF-8 safety

`props` is stored as JSON. The `Media` model encodes JSON using:

- `JSON_UNESCAPED_UNICODE`
- `JSON_INVALID_UTF8_SUBSTITUTE` (if available)

This prevents crashes on malformed UTF-8 input while preserving valid content.

## 6) Media API (used by advanced fields)

Advanced fields use `/admin/api/media/*` endpoints for:

- showing a media item + props (for modals)
- deleting a media item
- reordering a collection
- cropping an image

See: `media-api.md`.

### Security model

All destructive operations validate that the media record belongs to the current model via:

```php
$model->media()->whereIn('id', $ids)
```

Additionally, `adv_inline_set` per-row deletion is restricted by `collection_name` (row-scoped).
