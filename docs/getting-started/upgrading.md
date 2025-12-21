# Upgrading

## Upgrading 1.5 to 1.6

### Update your Composer.json

To update to the latest version inside of your composer.json file make sure to update the version of Voyager inside the require declaration of your composer.json to:

`tcg/voyager": "1.6.*`

And then run `composer update`

### Check your rich text configuration

Voyager no longer ships TinyMCE. The `rich_text_box` field now mounts [Jodit Editor](https://xdsoft.net/jodit/), so any legacy `window.voyagerTinyMCE...` customizations will stop working.

If you previously injected custom TinyMCE scripts/options, remove them and use the new lazy-loaded editors API instead: `Voyager.loadEditors().then(({ initJodit }) => initJodit(...))` (documented in the Rich Text form field guide).

### Troubleshooting

Be sure to ask us on our slack channel if you are experiencing any issues and we will try and assist. Thanks.

## Notes for this fork

This repository diverges from the original Voyager 1.x line and includes breaking modernizations:

- TinyMCE is removed in favor of Jodit (see `../bread/formfields/rich-text.md`).
- Legacy Webpack/Mix build pipeline is replaced by Vite (see `../fork/assets-and-editors.md`).
- Some advanced features are migrated from a legacy `voyager-extension` codebase (see `../fork/overview.md`).
