# BREAD Details Options (this fork)

Voyager supports a set of additional JSON options for BREAD fields.  
Add these to the **Details** JSON for a field in **Tools -> BREAD -> Edit BREAD**.

## Browse list options

### Column order

```json
{ "browse_order": 10 }
```

Lower values appear earlier. Use integers to define ordering.

### Column title

```json
{ "browse_title": "Short Title" }
```

### Alignment / width / font size

```json
{
  "browse_align": "text-right",
  "browse_width": "140px",
  "browse_font_size": "12px"
}
```

`browse_align` expects a CSS class (e.g. `text-left`, `text-center`, `text-right`).

### Inline editing (text/number)

```json
{ "browse_inline_editor": true }
```

Enables inline editing on browse for `text` and `number` types (requires edit permission).

### Inline checkbox toggle

```json
{
  "on": "Enabled",
  "off": "Disabled",
  "browse_inline_checkbox": true
}
```

Shows a toggle for `checkbox` fields on the browse list.

### Image thumbnail size

```json
{ "browse_image_max_height": "30px" }
```

Applies to the `image` field type in browse mode.

### Clickable text (route helpers)

```json
{ "url": "edit" }
```

Generates a link to a Voyager route: `voyager.{slug}.edit`.

Custom route name with parameter:

```json
{
  "route": {
    "name": "site.page.show",
    "param_field": "slug"
  }
}
```

## Tree browse mode

If a model uses `parent_id`, you can enable a tree view:

```json
{ "browse_tree": true }
```

You can also push fields to the right:

```json
{ "browse_tree_push_right": true }
```

## Edit/Add layout

### Tabs

```json
{ "tab_title": "SEO" }
```

When a field has `tab_title`, it starts a new tab section in the edit/add screen.  
The first tab is created automatically.
