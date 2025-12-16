<p align="center"><a href="https://voyager.devdojo.com" target="_blank"><img width="400" src="https://s3.amazonaws.com/thecontrolgroup/voyager.png"></a></p>

# **V**oyager - The Missing Laravel Admin
Made with ❤️ by [The Control Group](https://www.thecontrolgroup.com)

![Voyager Screenshot](https://s3.amazonaws.com/thecontrolgroup/voyager-screenshot.png)

Website & Documentation: https://voyager.devdojo.com/

Video Tutorial Here: https://voyager.devdojo.com/academy/

Join our Slack chat: https://voyager-slack-invitation.herokuapp.com/

View the Voyager Cheat Sheet: https://voyager-cheatsheet.ulties.com/

<hr>

> **⚠️ IMPORTANT NOTE: Voyager Reborn (v1.8+)**
>
> This repository is a modernized, drop-in replacement for the original TCG Voyager, revived to support the latest PHP and Laravel ecosystems.
>
> **Key Modernization Features:**
> *   **Modern Stack Support:** Fully compatible with **PHP 8.2+** and **Laravel 11 / 12**.
> *   **Zero Legacy Dependencies:** Removed `jQuery`, `Intervention/Image v2`, `TinyMCE`, and other outdated libraries.
> *   **New Rich Text Editor:** Replaced TinyMCE with **Jodit Editor** (lightweight, fast, and reliable).
> *   **Native Image Processing:** Replaced Intervention with a custom lightweight GD-based processor (faster, no external deps).
> *   **Modern Build Tooling:** Migrated from Webpack/Mix to **Vite**.
> *   **CSS Grid Shim:** Replaced Bootstrap 3's float-based grid with a modern **Flexbox implementation** (preserving visual compatibility).
> *   **Google Maps:** Updated to work with modern APIs (removed dependency on deprecated Map IDs).
>
> This version aims to keep the classic Voyager experience alive while running on a strictly modern technology stack.

Laravel Admin & BREAD System (Browse, Read, Edit, Add, & Delete), supporting Laravel 8 and newer!

> Want to use Laravel 6 or 7? Use [Voyager 1.5](https://github.com/the-control-group/voyager/tree/1.5)

## Installation Steps

### 1. Require the Package

After creating your new Laravel application you can include the Voyager package with the following command:

```bash
composer require tcg/voyager
```

> If you are installing this on Laravel 10, we are working on getting a permanent release available; however, you can still use this with Larvel 10 by requiring the following:

```bash
composer require tcg/voyager dev-1.6-l10
```

### 2. Add the DB Credentials & APP_URL

Next make sure to create a new database and add your database credentials to your .env file:

```
DB_HOST=localhost
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret
```

You will also want to update your website URL inside of the `APP_URL` variable inside the .env file:

```
APP_URL=http://localhost:8000
```

### 3. Run The Installer

Lastly, we can install voyager. You can do this either with or without dummy data.
The dummy data will include 1 admin account (if no users already exists), 1 demo page, 4 demo posts, 2 categories and 7 settings.

To install Voyager without dummy simply run

```bash
php artisan voyager:install
```

If you prefer installing it with dummy run

```bash
php artisan voyager:install --with-dummy
```

And we're all good to go!

Start up a local development server with `php artisan serve` And, visit [http://localhost:8000/admin](http://localhost:8000/admin).

## Creating an Admin User

If you did go ahead with the dummy data, a user should have been created for you with the following login credentials:

>**email:** `admin@admin.com`   
>**password:** `password`

NOTE: Please note that a dummy user is **only** created if there are no current users in your database.

If you did not go with the dummy user, you may wish to assign admin privileges to an existing user.
This can easily be done by running this command:

```bash
php artisan voyager:admin your@email.com
```

If you did not install the dummy data and you wish to create a new admin user, you can pass the `--create` flag, like so:

```bash
php artisan voyager:admin your@email.com --create
```

And you will be prompted for the user's name and password.

## Frontend Assets

Voyager's admin panel assets are built with [Vite](https://vitejs.dev/). When working on the UI locally run:

```bash
npm install
npm run build
php artisan vendor:publish --tag=voyager_assets --force
```

The `vendor:publish` step copies the compiled files from `publishable/assets` into your application's `public/vendor/voyager` directory so that the admin panel can serve them directly.
This path is now the only runtime source of Voyager's CSS/JS, so rerun the publish command every time you rebuild assets.

## Additions in this fork

This fork includes additional features migrated from a legacy `voyager-extension` codebase and a custom Media Storage subsystem (instead of Spatie Media Library).

**Modernization summary (fork)**
- Modern stack: PHP 8.2+ and Laravel 11/12
- Build: Webpack/Mix → Vite (`publishable/assets` → publish to `public/vendor/voyager`)
- Editors: TinyMCE → Jodit; Ace is bundled and lazy-loaded via `js/editors.js`
- JS: native JS modules + bootstrap compatibility layer; unified confirm modals
- Media: custom polymorphic `media` table + media API + CropperJS crop UI

**Custom BREAD formfields**
- `adv_select_dropdown_tree` (hierarchical dropdown)
- `adv_related` (native JS autocomplete + sortable)
- `adv_image` (single media item with props + crop)
- `adv_media_files` (gallery/collection with props, reorder, bulk, crop)
- `adv_inline_set` (inline sets, JSON-only storage, supports Jodit/Ace/media)
- `adv_json`, `adv_fields_group`

**Media Storage**
- Polymorphic `media` table (`model_type/model_id`), `collection_name`, `order`, `props`, `disk`, `path`
- Services: `TCG\\Voyager\\Services\\MediaService`, `MediaUploadService`, `PathGeneratorService`
- Dated path strategy: `{table}/media/{Y}/{m}`

For fork-specific documentation see:
- `docs/fork/overview.md`
- `docs/fork/media-storage.md`

For implementation/migration notes (workspace):
- `E:\\www.osp6\\voyager\\VE-MIGRATION.md`
- `E:\\www.osp6\\voyager\\ADMIN-REVIEW.md`

## Sponsors

Voyager is proudly supported by our amazing sponsors. A big thank you to:

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.cdn.digitaloceanspaces.com/WWW/Badge%203.svg)](https://www.digitalocean.com/?refcode=dc19b9819d06&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)

