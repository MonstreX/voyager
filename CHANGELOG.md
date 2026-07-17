# Changelog

All notable changes to this fork are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.8.6] - 2026-07-17

### Platform and compatibility

- Added support for Laravel 11 and Laravel 12.
- Updated the package baseline to PHP 8.2+.
- Restored database tools compatibility with Doctrine DBAL 4.
- Modernized package installation: Voyager assets and configuration are published automatically.

### Modern frontend stack

- Replaced Laravel Mix with Vite.
- Migrated the admin frontend to Vue 3 and native ES modules.
- Removed the jQuery dependency and replaced legacy jQuery-based interactions with native JavaScript.
- Replaced legacy UI dependencies and implementations: Select2, Bootstrap JS plugins,
  bootstrap-toggle, jquery.nestable, jQuery UI, legacy date pickers, Toastr and DataTables.
- Added modern replacements: Voyager Select, native toast notifications, SortableJS,
  Flatpickr and vanilla Bootstrap compatibility helpers.
- Reworked asset loading, lazy initialization and cache busting.

### BREAD improvements

- Added tabbed layouts for BREAD add/edit forms.
- Added a sticky action panel for edit forms.
- Added configurable browse-table presentation: column title, width, alignment and font size.
- Added inline editing for text and number fields in browse tables.
- Added browse filters.
- Added configurable text links and routes in browse lists.
- Added clone-record action with per-model configuration.
- Added tree browse mode and improved ordering, drag-and-drop and action handling.
- Improved JSON editing and formatting for BREAD field definitions.
- Added previews for `adv_image` and `adv_media_files` fields in standard BREAD browse tables.

### Advanced form fields

- Added `adv_fields_group` for grouped structured fields, including inline editing in browse mode.
- Added `adv_json` for repeatable JSON field sets.
- Added `adv_select_dropdown_tree` for hierarchical selections.
- Added `adv_related` with native autocomplete.
- Added `adv_inline_set` for inline related record sets.

### Media library and image handling

- Added `adv_image` backed by the media library.
- Added `adv_media_files` for media collections with sorting, metadata and file management.
- Added image cropping for advanced media fields, including aspect-ratio selection and constraints.
- Added sorting and cropping support for legacy multiple-image fields.
- Improved media upload, deletion, crop handling, authorization, CSRF handling and API error responses.
- Fixed `adv_image` uploads for newly created records.
- Replaced Intervention Image with a built-in GD-based image processor.
- Added cache-busting for media and image URLs.

### Editors and admin interface

- Migrated rich-text editing to Jodit Editor.
- Improved Ace editor loading and automatic expansion.
- Improved date and timestamp editing with Flatpickr.
- Reworked admin layout from the legacy float grid to a Flexbox-based layout.
- Refined menus, modals, confirmations, settings, dashboard widgets and form layouts.
- Added consistent confirmation dialogs across admin actions.

### Reliability and developer experience

- Added HTTP admin tests and expanded media subsystem coverage.
- Refactored BREAD, media, menu, settings and tools scripts into maintainable modules.
- Improved model generation defaults and permission generation.
- Added safer handling for legacy database column types and incomplete advanced-field definitions.

[Unreleased]: https://github.com/MonstreX/voyager/compare/v1.8.6...HEAD
[1.8.6]: https://github.com/MonstreX/voyager/releases/tag/v1.8.6
