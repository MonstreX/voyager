# Menu Item Status (this fork)

This fork adds an `status` field to `menu_items` and exposes it in the Menu Builder UI.

The main goal is to allow enabling/disabling menu items without deleting them.

## 1) Database and model

Migration:
- adds `menu_items.status` as a tiny integer with default `1`

Model:
- `TCG\Voyager\Models\MenuItem` casts `status` to integer and sets default `1`

## 2) UI behaviour

### 2.1 Menu item edit modal

The status control is displayed as the first field in the modal and uses the standard Voyager toggle (`.toggleswitch`).

Default is **enabled** (`1`).

### 2.2 Tree view (menu builder list)

Each menu item row shows a small status indicator/toggle.
Toggling it triggers an async request to update status without reloading the page.

## 3) API endpoint

The Menu Builder uses a route similar to:

```
POST /admin/menus/{menu}/item/{id}/status
```

Payload:
- `status`: `0` or `1`

Response is JSON with `status: success|error`.

## 4) Rendering menus

When rendering menus for the application (front-end), you usually want to filter out inactive items:

```php
$items = menu('main', '_json')->filter(fn ($item) => (int) ($item->status ?? 1) === 1);
```

If you use `menu('main')` (HTML rendering), implement filtering inside your custom menu view:

```blade
<ul>
@foreach($items as $menu_item)
    @continue((int) $menu_item->status === 0)
    <li><a href="{{ $menu_item->link() }}">{{ $menu_item->title }}</a></li>
@endforeach
</ul>
```

## 5) Notes

- Status is treated as a simple integer flag (`1` = active, `0` = disabled).
- If you need more states (draft/archived/etc.), extend the schema and UI accordingly.

