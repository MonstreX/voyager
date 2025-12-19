<?php

namespace TCG\Voyager\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Models\Category;
use TCG\Voyager\Tests\TestCase;

class InlineUpdateFieldTest extends TestCase
{
    public function testCanInlineUpdateAllowedField()
    {
        Auth::loginUsingId(1);

        $category = Category::create([
            'name' => 'Inline Old',
            'slug' => 'inline-old',
            'order' => 1,
        ]);

        $response = $this->call('POST', route('voyager.categories.update-field', [$category->id]), [
            'slug' => 'categories',
            'field' => 'name',
            'value' => 'Inline New',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $category->refresh();
        $this->assertEquals('Inline New', $category->name);
    }

    public function testInlineUpdateRejectsForbiddenField()
    {
        Auth::loginUsingId(1);

        $category = Category::create([
            'name' => 'Inline Forbidden',
            'slug' => 'inline-forbidden',
            'order' => 1,
        ]);

        $response = $this->call('POST', route('voyager.categories.update-field', [$category->id]), [
            'slug' => 'categories',
            'field' => 'id',
            'value' => 999,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['status' => 'error']);
    }
}

