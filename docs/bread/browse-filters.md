# Browse Filters (this fork)

This fork adds optional, session-persisted filters to the BREAD browse list (`/admin/{slug}`).

These filters are configured per-field in the BREAD builder and are rendered as `<select>` controls above the table.

## 1) Enabling a filter for a field

In **Tools → BREAD → Edit BREAD**, open a field and add the following to **Details**:

```json
{
  "browse_filter": true
}
```

Currently the browse filters are designed primarily for **relationship-backed fields** (tree/hierarchical lists).

## 2) Relationship filters (recommended)

If a field has a `relationship` configuration in its details, the browse filter will use:

- `relationship.model` to query filter items
- `relationship.ref_field` as the column to filter by
- `relationship.key` as the option value
- `relationship.label` as the option label

Example:

```json
{
  "browse_filter": true,
  "relationship": {
    "model": "App\\\\Models\\\\Category",
    "table": "categories",
    "type": "belongsTo",
    "column": "category_id",
    "key": "id",
    "label": "name",
    "ref_field": "category_id",
    "filter_label": "Category"
  }
}
```

### Tree flattening

The UI uses a flattened tree representation with indentation (`--`) based on the `level` key produced by the helper functions:

- `flat_to_tree()`
- `build_flat_from_tree()`

This makes the dropdown readable for nested structures.

## 3) URL format

The browse filter state is expressed as two arrays:

```
?field[0]=category_id&value[0]=3
```

Multiple filters can be combined:

```
?field[0]=category_id&value[0]=3&field[1]=status&value[1]=1
```

## 4) Session persistence

Filters are persisted in the session so you can navigate away and come back without losing them.

Rules:

- If the BREAD slug changes, stored filters are cleared automatically.
- If you pass `reset_filters=1`, stored filters are cleared.
- If `field[]`/`value[]` are present, they overwrite the session state.

## 5) How the query is applied

In the browse controller, every filter pair is applied as:

```php
$query->where($field, '=', $value);
```

This is intentionally strict/equality-based.

## 6) Notes / limitations

- Only fields with `browse_filter: true` are shown.
- Relationship filters expect that the related model can be queried without additional scoping.
- If you need complex filtering (ranges, LIKE, null checks), implement it as a custom scope and/or a custom browse page.

