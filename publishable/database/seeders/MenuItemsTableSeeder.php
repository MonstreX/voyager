<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;

class MenuItemsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file.
     *
     * @return void
     */
    public function run()
    {
        $refresh = config('voyager.seed_refresh', false);
        $menu = Menu::where('name', 'admin')->firstOrFail();

        $this->seedMenuItem($refresh, $menu, 'voyager.dashboard', ['route' => 'voyager.dashboard', 'icon_class' => 'voyager-boat'], [
            'title' => __('voyager::seeders.menu_items.dashboard'),
            'key' => 'voyager.dashboard',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-boat',
            'color' => null,
            'parent_id' => null,
            'order' => 1,
        ]);

        $this->seedMenuItem($refresh, $menu, 'voyager.media', ['route' => 'voyager.media.index', 'icon_class' => 'voyager-images'], [
            'title' => __('voyager::seeders.menu_items.media'),
            'key' => 'voyager.media',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-images',
            'color' => null,
            'parent_id' => null,
            'order' => 5,
        ]);

        $this->seedMenuItem($refresh, $menu, 'voyager.users', ['route' => 'voyager.users.index', 'icon_class' => 'voyager-person'], [
            'title' => __('voyager::seeders.menu_items.users'),
            'key' => 'voyager.users',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-person',
            'color' => null,
            'parent_id' => null,
            'order' => 3,
        ]);

        $this->seedMenuItem($refresh, $menu, 'voyager.roles', ['route' => 'voyager.roles.index', 'icon_class' => 'voyager-lock'], [
            'title' => __('voyager::seeders.menu_items.roles'),
            'key' => 'voyager.roles',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-lock',
            'color' => null,
            'parent_id' => null,
            'order' => 2,
        ]);

        $toolsMenuItem = $this->seedMenuItem($refresh, $menu, 'voyager.tools', ['route' => null, 'icon_class' => 'voyager-tools', 'url' => ''], [
            'title' => __('voyager::seeders.menu_items.tools'),
            'key' => 'voyager.tools',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-tools',
            'color' => null,
            'parent_id' => null,
            'order' => 9,
        ]);

        $this->seedMenuItem($refresh, $menu, 'voyager.menu_builder', ['route' => 'voyager.menus.index', 'icon_class' => 'voyager-list'], [
            'title' => __('voyager::seeders.menu_items.menu_builder'),
            'key' => 'voyager.menu_builder',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-list',
            'color' => null,
            'parent_id' => $toolsMenuItem->id,
            'order' => 10,
        ]);

        $this->seedMenuItem($refresh, $menu, 'voyager.database', ['route' => 'voyager.database.index', 'icon_class' => 'voyager-data'], [
            'title' => __('voyager::seeders.menu_items.database'),
            'key' => 'voyager.database',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-data',
            'color' => null,
            'parent_id' => $toolsMenuItem->id,
            'order' => 11,
        ]);

        $this->seedMenuItem($refresh, $menu, 'voyager.compass', ['route' => 'voyager.compass.index', 'icon_class' => 'voyager-compass'], [
            'title' => __('voyager::seeders.menu_items.compass'),
            'key' => 'voyager.compass',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-compass',
            'color' => null,
            'parent_id' => $toolsMenuItem->id,
            'order' => 12,
        ]);

        $this->seedMenuItem($refresh, $menu, 'voyager.bread', ['route' => 'voyager.bread.index', 'icon_class' => 'voyager-bread'], [
            'title' => __('voyager::seeders.menu_items.bread'),
            'key' => 'voyager.bread',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-bread',
            'color' => null,
            'parent_id' => $toolsMenuItem->id,
            'order' => 13,
        ]);

        $this->seedMenuItem($refresh, $menu, 'voyager.settings', ['route' => 'voyager.settings.index', 'icon_class' => 'voyager-settings'], [
            'title' => __('voyager::seeders.menu_items.settings'),
            'key' => 'voyager.settings',
            'url' => '',
            'target' => '_self',
            'icon_class' => 'voyager-settings',
            'color' => null,
            'parent_id' => null,
            'order' => 14,
        ]);

        Cache::forget('voyager_menu_'.$menu->name);
    }

    protected function seedMenuItem(bool $refresh, Menu $menu, string $key, array $criteria, array $payload): MenuItem
    {
        $criteria = array_merge(['menu_id' => $menu->id], $criteria);
        $payload = array_merge(['menu_id' => $menu->id], $payload);

        $itemByKey = MenuItem::whereNotNull('key')
            ->where('menu_id', $menu->id)
            ->where('key', $key)
            ->first();
        $itemByCriteria = MenuItem::where($criteria)->whereNull('key')->first();
        if (!$itemByCriteria) {
            $itemByCriteria = MenuItem::where($criteria)->first();
        }

        if ($itemByCriteria && $itemByKey && $itemByCriteria->id !== $itemByKey->id) {
            if (!$refresh) {
                return $itemByKey;
            }
            $itemByKey->delete();
            if (empty($itemByCriteria->key)) {
                $itemByCriteria->key = $key;
            }
            $itemByCriteria->fill($this->refreshPayload($payload));
            $itemByCriteria->save();
            return $itemByCriteria;
        }

        if ($itemByKey) {
            if ($refresh) {
                $itemByKey->fill($this->refreshPayload($payload))->save();
            }
            return $itemByKey;
        }

        if ($itemByCriteria) {
            if (empty($itemByCriteria->key)) {
                $itemByCriteria->key = $key;
            }
            if ($refresh) {
                $itemByCriteria->fill($this->refreshPayload($payload));
            }
            $itemByCriteria->save();
            return $itemByCriteria;
        }

        return MenuItem::create($payload);
    }

    protected function refreshPayload(array $payload): array
    {
        unset($payload['parent_id'], $payload['order']);

        return $payload;
    }
}
