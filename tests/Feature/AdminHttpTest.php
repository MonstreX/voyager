<?php

namespace TCG\Voyager\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Tests\TestCase;

class AdminHttpTest extends TestCase
{
    public function testLoginPageLoads()
    {
        $response = $this->call('GET', route('voyager.login'));

        $this->assertEquals(200, $response->status());
    }

    public function testCanLoginUsingDefaultCredentials()
    {
        $response = $this->call('POST', route('voyager.login'), [
            'email'    => 'admin@admin.com',
            'password' => 'password',
        ]);

        $this->assertEquals(302, $response->status());
        $this->assertEquals(route('voyager.dashboard'), $response->headers->get('Location'));
    }

    public function testAuthenticatedUserCanSeeDashboard()
    {
        Auth::loginUsingId(1);

        $response = $this->call('GET', route('voyager.dashboard'));

        $this->assertEquals(200, $response->status());
    }

    public function testAuthenticatedUserCanAccessCategoriesBread()
    {
        Auth::loginUsingId(1);

        $index = $this->call('GET', route('voyager.categories.index'));
        $this->assertEquals(200, $index->status());

        $create = $this->call('GET', route('voyager.categories.create'));
        $this->assertEquals(200, $create->status());
    }

    public function testDatabaseManagerIsAccessibleForAdmins()
    {
        Auth::loginUsingId(1);

        $response = $this->call('GET', route('voyager.database.index'));

        $this->assertEquals(200, $response->status());
    }
}
