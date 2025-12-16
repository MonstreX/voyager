# Menu Item Status (Menu Builder)

This fork adds a `status` field to menu items and exposes it in the Menu Builder UI.

For the full developer guide see: `../core-concepts/menu-item-status.md`.

## Behaviour

- `status = 1` means the menu item is enabled (default).
- `status = 0` means disabled.

The toggle is shown:
- as the first control in the “edit menu item” modal
- as the first column in the menu items row (before title), including tree view

## Data model

- Stored on the `menu_items` table as an integer/boolean field.

If you render menus on the frontend, you can use the `status` flag to exclude disabled items (implementation depends on your menu rendering view).
