# Advanced Fields Group (`adv_fields_group`)

`adv_fields_group` is a layout/helper field used to group multiple fields in the BREAD form.

This field is migrated from a legacy `voyager-extension` implementation. It does not store data by itself; it affects only the UI layout.

## Recommended usage

Use `adv_fields_group` when:
- you have a long form and want visual grouping
- you want consistent section headers inside the BREAD edit/add screen

## Details JSON

The exact layout options depend on the current view template, but a typical pattern is:

```json
{
  "title": "SEO",
  "description": "Metadata for the page",
  "class": "col-md-12"
}
```

## Notes

- This field should not be used for actual data storage.
- Prefer grouping via `adv_fields_group` over duplicating custom Blade overrides.
