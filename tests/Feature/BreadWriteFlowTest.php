<?php

namespace TCG\Voyager\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Models\Category;
use TCG\Voyager\Tests\TestCase;

class BreadWriteFlowTest extends TestCase
{
    public function testCanCreateCategoryViaBreadStore()
    {
        Auth::loginUsingId(1);

        $response = $this->call('POST', route('voyager.categories.store'), [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'order' => 1,
        ]);

        $this->assertEquals(302, $response->status());

        $this->assertTrue(Category::where('slug', 'test-category')->exists());
    }

    public function testCanUpdateCategoryViaBreadUpdate()
    {
        Auth::loginUsingId(1);

        $category = Category::create([
            'name' => 'Old Category',
            'slug' => 'old-category',
            'order' => 1,
        ]);

        $response = $this->call('PUT', route('voyager.categories.update', [$category->id]), [
            'name' => 'New Category',
            'slug' => 'new-category',
            'order' => 1,
        ]);

        $this->assertEquals(302, $response->status());

        $category->refresh();
        $this->assertEquals('New Category', $category->name);
        $this->assertEquals('new-category', $category->slug);
    }
}
