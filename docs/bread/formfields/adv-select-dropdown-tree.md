# Advanced Select Dropdown Tree (`adv_select_dropdown_tree`)

`adv_select_dropdown_tree` renders a hierarchical dropdown for `belongsTo` relationships where the related table represents a parent/child tree (usually via `parent_id`).

## Details JSON

```json
{
  "relationship": {
    "model": "App\\\\Models\\\\Category",
    "field": "category",
    "key": "id",
    "label": "name",
    "ref_field": "category_id"
  }
}
```

### Options

- `model`: related model class.
- `field`: relationship method name on the current model.
- `key`: primary key on the related model.
- `label`: display field on the related model.
- `ref_field`: foreign key field on the current model.

## Behaviour

- Browse/Read display shows the related label instead of the raw ID.
- Edit/Add view shows a `<select>` built from a flattened tree, with `--` indentation based on depth.

