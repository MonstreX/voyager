<p align="center"><img width="400" src="https://s3.amazonaws.com/thecontrolgroup/voyager.png" alt="Voyager"></p>

# Voyager

This repository is a fork of the classic **TCG Voyager** admin panel.  
The goal is to preserve the classic Voyager experience while running on a modern PHP/Laravel stack and keeping the codebase actively maintained.

## What changed in this fork

- **Modern stack:** PHP 8.2+ and Laravel 11/12.
- **Build tooling:** Webpack/Mix -> Vite.
- **Editors:** TinyMCE -> Jodit (lazy-loaded); Ace for code fields.
- **Media layer:** Custom Media Storage subsystem (no Spatie Media Library).
- **Advanced BREAD fields:** `adv_image`, `adv_media_files`, `adv_inline_set`, `adv_related`, `adv_select_dropdown_tree`, `adv_json`, `adv_fields_group`.
- **UX improvements:** browse filters, inline editing, tree view, sticky action panel, improved media crop UI.

This fork is not affiliated with The Control Group and is maintained independently.

## Installation

```bash
composer require monstrex/voyager
```

Run the installer (with or without dummy data):

```bash
php artisan voyager:install
# or
php artisan voyager:install --with-dummy
```

### Installer options

```
--with-dummy   Install demo data (pages, posts, categories, settings)
--force        Force operations in production
--refresh      Refresh Voyager seed data only (no migrations)
--locale=xx    Locale to use when refreshing seed data
```

### Refresh seed data

Use this when you want to restore/update Voyager BREAD, menus, permissions,
and settings to the package defaults without re-installing:

```bash
php artisan voyager:install --refresh
```

With a specific locale:

```bash
php artisan voyager:install --refresh --locale=ru
```

## Creating an Admin User

```bash
php artisan voyager:admin your@email.com
```

To create a new admin user in one step:

```bash
php artisan voyager:admin your@email.com --create
```

## Frontend Assets (Admin UI)

Voyager's admin assets are built with Vite. When you modify frontend assets:

```bash
npm install
npm run build
php artisan vendor:publish --tag=voyager_assets --force
```

Published assets live in `public/vendor/voyager` and are required at runtime.

## Uninstall (manual)

Voyager does not ship a dedicated uninstall command. To remove it:

1) Remove the package:

```bash
composer remove monstrex/voyager
```

2) Roll back or delete Voyager tables (if you want to clean the DB).

3) Remove the Voyager routes block from `routes/web.php`:

```php
Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
```

4) Delete published assets/config if needed:

- `public/vendor/voyager`
- `config/voyager.php`

5) (Optional) Remove the storage symlink:

```
public/storage
```

## Documentation

Docs live in `docs/` and include fork-specific guides:

- `docs/fork/overview.md`
- `docs/fork/assets-and-editors.md`
- `docs/fork/media-storage.md`
