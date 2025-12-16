# Advanced Related (`adv_related`)

`adv_related` is a native JavaScript autocomplete field for selecting related records and storing a compact JSON payload in the model column. It supports drag-and-drop ordering via SortableJS.

This field is useful when you want:
- an ordered list of related entities
- to store a snapshot of selected fields (id/title/slug/etc.)
- to avoid building pivot tables for simple use cases

## Details JSON

```json
{
  "related_model": {
    "source": "posts",
    "search_field": "title",
    "display_field": "title",
    "fields": ["id", "title", "slug"]
  }
}
```

### Options

- `source` (string): BREAD slug (DataType slug) used by the search endpoint.
- `search_field` (string): field used for searching.
- `display_field` (string): field shown as the item title in the UI.
- `fields` (array): fields included in the stored JSON payload.

## Search endpoint

The autocomplete queries an admin route and expects JSON results:

- Route: `voyager.related-records.search`
- Parameters (from field `details`): `slug`, `search_field`, `display_field`, `fields`

Typical URL shape:

```
GET /admin/related-records/search?slug=posts&s=term&search_field=title&display_field=title&fields=id,title,slug
```

## Stored JSON format

```json
[
  {
    "display_field": "title",
    "fields": {
      "id": 1,
      "title": "Example",
      "slug": "example"
    }
  }
]
```

## Typical usage pattern

Extract ids:

```php
$items = json_decode($model->related_field, true) ?: [];
$ids = collect($items)->pluck('fields.id')->filter()->values();
```

Extract display values:

```php
$titles = collect($items)->map(fn ($item) => data_get($item, 'fields.title'));
```
