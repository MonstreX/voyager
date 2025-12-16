# Advanced Related (`adv_related`)

`adv_related` is a native JavaScript autocomplete field for selecting related records and storing a compact JSON payload in the model column. It supports drag-and-drop ordering via SortableJS.

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

## Endpoint

The autocomplete queries an admin route and expects JSON results:

- Route: `voyager.related-records.search`
- Parameters (from field `details`): `slug`, `search_field`, `display_field`, `fields`

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
