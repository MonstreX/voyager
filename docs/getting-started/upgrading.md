# Upgrading

## Upgrading from the original TCG Voyager

1) Update your Composer dependency:

```
composer require monstrex/voyager
```

2) Run an update and migrations:

```
composer update
php artisan migrate
```

3) Publish updated assets (required after any UI changes):

```
php artisan vendor:publish --tag=voyager_assets --force
```

4) If you want the latest config defaults:

```
php artisan vendor:publish --tag=config --provider="TCG\Voyager\VoyagerServiceProvider"
```

5) Clear caches:

```
php artisan config:clear
php artisan view:clear
```

## Notes for this fork

- **TinyMCE is removed** -> `rich_text_box` uses **Jodit** (lazy-loaded).
- **Webpack/Mix replaced by Vite** -> rebuild assets and re-publish.
- **Media Storage** replaces Spatie Media Library from legacy extensions.
- Several advanced BREAD features are merged from the legacy `voyager-extension` package.

See `docs/fork/overview.md` for the full list of changes.
