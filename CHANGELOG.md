# Changelog

All notable changes to this fork are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.8.6] - 2026-07-17

This entry records the main changes introduced by the fork. Each item includes
the timestamp of its original implementation commit (`UTC+05:00`).

### 2025-11-24

- **11:01:15** — Added Laravel 12 compatibility scaffolding.
- **13:15:51** — Restored database tools compatibility with Doctrine DBAL 4.
- **15:26:13** — Made generated models use `app/Models` by default.
- **19:04:44** — Added HTTP admin tests and isolated the legacy BrowserKit suite.

### 2025-11-25

- **08:52:54** — Migrated the frontend build from Laravel Mix to Vite and upgraded the admin bridge to Vue 3.

### 2025-11-30

- **14:00:55** — Replaced DataTables with lightweight vanilla tables.
- **18:31:29** — Added Flatpickr date and timestamp pickers.
- **19:04:03** — Replaced Bootstrap Toggle with a vanilla implementation.
- **19:42:43** — Replaced Bootstrap JavaScript plugins with vanilla compatibility helpers.
- **20:32:55** — Replaced Select2 with the native Voyager Select component.

### 2025-12-01

- **10:15:39** — Replaced jquery.nestable with SortableJS.
- **17:23:25** — Replaced Toastr with a native toast notification component.

### 2025-12-02

- **10:17:46** — Removed the jQuery dependency and migrated remaining interactions to native JavaScript.

### 2025-12-04

- **12:48:25** — Migrated rich-text editing from TinyMCE to Jodit Editor.

### 2025-12-06

- **16:51:08** — Replaced Intervention Image with a built-in GD-based image processor.
- **17:28:14** — Reworked the legacy float grid into a Flexbox-based admin layout.
- **17:54:27** — Set the supported platform baseline to PHP 8.2+ and Laravel 11/12.

### 2025-12-10

- **20:18:26** — Added a sticky action panel to BREAD edit forms.
- **22:44:39** — Added tabbed layouts to BREAD add/edit forms.

### 2025-12-11

- **09:15:36** — Added browse-table presentation options: title, width, alignment and font size.
- **10:55:00** — Added tree browse mode.
- **12:34:53** — Added configurable text links and routes in browse lists.
- **15:31:01** — Added inline editing for text and number fields in browse tables.
- **20:31:43** — Added clone-record action with per-model configuration.

### 2025-12-12

- **12:51:36** — Added `adv_fields_group`, including structured grouped fields and browse editing.
- **14:57:40** — Added `adv_json` for repeatable JSON field sets.
- **16:04:27** — Added `adv_select_dropdown_tree` for hierarchical selections.
- **17:00:14** — Added BREAD browse filters.
- **17:21:36** — Added `adv_related` with native autocomplete.

### 2025-12-13

- **16:27:01** — Added `adv_image` backed by the media library.

### 2025-12-14

- **20:12:57** — Added `adv_media_files` for media collections with sorting, metadata and file management.

### 2025-12-15

- **19:00:13** — Added image cropping for `adv_media_files`.

### 2025-12-16

- **10:22:13** — Added crop modal support for `adv_image`.
- **16:03:30** — Added `adv_inline_set` for inline related record sets.

### 2026-01-23

- **19:29:31** — Added sorting for legacy multiple-image fields.
- **20:31:55** — Added crop actions for legacy multiple-image fields.

### 2026-01-27

- **11:44:14** — Added dashboard system widgets.

### 2026-06-11

- **11:37:53** — Added safe fallback handling for advanced group fields with no explicit nested field type.

### 2026-06-15

- **12:51:34** — Fixed `adv_image` uploads for newly created records.

### 2026-07-17

- **11:13:59** — Added `adv_image` and `adv_media_files` previews to standard BREAD browse tables.

[Unreleased]: https://github.com/MonstreX/voyager/compare/v1.8.6...HEAD
[1.8.6]: https://github.com/MonstreX/voyager/releases/tag/v1.8.6
