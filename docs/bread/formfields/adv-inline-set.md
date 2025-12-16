# Advanced Inline Set (`adv_inline_set`)

`adv_inline_set` renders a repeatable "row" of configured sub-fields and stores all values as JSON in the model column (JSON-only mode).

This field is migrated from a legacy `voyager-extension` implementation. The original "source model/table storage" mode is intentionally not supported in this fork.

## Details JSON

```json
{
  "inline_set": {
    "many": true,
    "columns": 2,
    "fields": {
      "date": { "label": "Date", "type": "date" },
      "select": {
        "label": "Select",
        "type": "select",
        "options": { "val1": "Option One", "val2": "Option Two" },
        "default": "val2"
      },
      "checkbox": { "label": "Enabled", "type": "checkbox", "on": "On", "off": "Off", "default": "on" },
      "rich_text": { "label": "Content", "type": "richtext", "min_height": 150 },
      "code": { "label": "Code", "type": "code", "mode": "html", "theme": "monokai", "minlines": 3, "maxlines": 20 },
      "image": { "label": "Media", "type": "media", "accept": "image/*,.pdf" }
    }
  }
}
```

### Options

- `many` (bool): allow multiple rows.
- `columns` (int): layout columns (1..6).
- `fields` (object): map of sub-fields.

### Supported sub-field types

- `text`, `textarea`, `number`, `date`
- `select` (requires `options`)
- `radio` (requires `options`)
- `checkbox` (bootstrap toggle; `on/off/default`)
- `richtext` (Jodit, `min_height`)
- `code` (Ace, `mode/theme/minlines/maxlines`)
- `media` (multiple files per row, sortable + delete; `accept`)

## Stored JSON format

```json
[
  {
    "row_id": 1,
    "order": 0,
    "date": "2025-12-16",
    "select": "val2",
    "checkbox": 1,
    "rich_text": "<p>...</p>",
    "code": "<div>...</div>",
    "image": [26, 27]
  }
]
```

Notes:
- `row_id` is a stable per-row identifier used by the UI.
- `order` is the current UI order.
- `media` stores an array of `media.id`.

## Practical examples

### 1) One item only (many=false)

```json
{
  "inline_set": {
    "many": false,
    "columns": 1,
    "fields": {
      "title": { "label": "Title", "type": "text", "class": "col-md-12" },
      "body": { "label": "Body", "type": "richtext", "min_height": 200, "class": "col-md-12" }
    }
  }
}
```

### 2) Repeatable blocks with media

```json
{
  "inline_set": {
    "many": true,
    "columns": 2,
    "fields": {
      "subtitle": { "label": "Subtitle", "type": "text" },
      "image": { "label": "Image(s)", "type": "media", "accept": "image/*" },
      "code": { "label": "HTML", "type": "code", "mode": "html", "theme": "monokai", "minlines": 4, "maxlines": 12 }
    }
  }
}
```

## Implementation notes (for developers)

- Media uploads on “create new record” are finalized after the first model `save()` so that `model_id` exists.
- Media deletions are validated server-side by ownership; per-row deletes are additionally restricted by `collection_name`.
