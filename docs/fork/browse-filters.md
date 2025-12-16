# Browse Filters (BREAD browse pages)

This fork includes additional filters on BREAD browse pages, migrated from a legacy implementation.

For the full developer guide see: `../bread/browse-filters.md`.

## What it does

- Adds a filter UI to BREAD browse tables.
- Persists filters in the session so pagination/sorting keeps the current filter set.
- Supports simple columns and relationship-backed filters.

## URL parameters

Filters can be applied via query parameters:

```
?field[0]=column_name&value[0]=some_value
```

Multiple filters:

```
?field[0]=status&value[0]=1&field[1]=category_id&value[1]=10
```

## Notes

- Some filters use Select2 dropdowns for better UX.
- If you customize browse views, ensure the filter component remains included so session persistence still works.
