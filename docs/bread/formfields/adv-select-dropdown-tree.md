# Advanced Select Dropdown Tree (`adv_select_dropdown_tree`)

Dropdown selector for hierarchical (parent/child) models.

## What editors can do

- Choose a parent item from a tree-like dropdown.
- Use it for categories, sections, menus, etc.

## How to add it in BREAD

1) Add a `parent_id` (or similar) column in your table.
2) In **Tools -> BREAD -> Edit BREAD**, set the field type to `adv_select_dropdown_tree`.
3) Add Details JSON with a relationship config.

## Details JSON (example)

```json
{
  "browse_filter": true,
  "relationship": {
    "model": "App\\\\Models\\\\Category",
    "key": "id",
    "label": "title",
    "field": "category",
    "ref_field": "category_id",
    "filter_label": "Category"
  }
}
```

### Options

- **model**: related model class
- **key**: primary key
- **label**: display label
- **ref_field**: local field referencing the related model
- **filter_label**: label for browse filters (if enabled)

## Notes

To use browse filters, enable `browse_filter: true` (see Browse Filters doc).

## Using in Blade / controllers

This field stores a standard foreign key (e.g. `category_id`).

```php
// In your model
public function category()
{
    return $this->belongsTo(Category::class);
}
```

```blade
{{ $post->category?->title }}
```
