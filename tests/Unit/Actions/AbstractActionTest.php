<?php

namespace TCG\Voyager\Tests\Unit\Actions;

use TCG\Voyager\Actions\AbstractAction;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\User;
use TCG\Voyager\Tests\TestCase;

class AbstractActionTest extends TestCase
{
    /**
     * The users DataType instance.
     *
     * @var \TCG\Voyager\Models\DataType
     */
    protected $userDataType;

    /**
     * A dummy user instance.
     *
     * @var \TCG\Voyager\Models\User
     */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->userDataType = Voyager::model('DataType')->where('name', 'users')->first();
        $this->user = \TCG\Voyager\Models\User::factory()->create();
    }

    /**
     * This test checks that `getRoute` method calls the `getDefaultRoute`
     * method if the given key is empty.
     */
    public function testGetRouteWithEmptyKey()
    {
        $action = new StubAction($this->userDataType, $this->user);
        $action->defaultRouteResult = true;

        $this->assertTrue($action->getRoute($this->userDataType->name));
    }

    /**
     * This test checks that `getRoute` method calls the expected method when a
     * key is given.
     */
    public function testGetRouteWithCustomKey()
    {
        $action = new StubAction($this->userDataType, $this->user);
        $action->customRouteResult = true;

        // The key that's passed to the `getRoute` method will be capitalized
        // and placed between 'get' and 'Route'. Calling `getRoute('custom')`
        // will call the `getCustomRoute` method if it's defined.
        $this->assertTrue($action->getRoute('custom'));
    }

    /**
     * This test checks that `getAttributes` method will give us the expected
     * output.
     */
    public function testConvertAttributesToHtml()
    {
        $action = new StubAction($this->userDataType, $this->user);
        $action->attributesResult = [
            'class'   => 'class1 class2',
            'data-id' => 5,
            'id'      => 'delete-5',
        ];

        $this->assertEquals('class="class1 class2" data-id="5" id="delete-5"', $action->convertAttributesToHtml());
    }

    /**
     * This test checks that `shouldActionDisplayOnDataType` method returns true
     * if the action should be displayed for every data type.
     */
    public function testShouldActionDisplayOnDataTypeWithDefaultDataType()
    {
        $action = new StubAction($this->userDataType, $this->user);

        $this->assertTrue($action->shouldActionDisplayOnDataType());
    }

    /**
     * This test checks that `shouldActionDisplayOnDataType` method returns true
     * if the action should only be displayed for a specific data type.
     */
    public function testTrueIsReturnedIfDataTypeMatchesTheOneWhereTheActionWasCreatedFor()
    {
        $action = new StubAction($this->userDataType, $this->user);
        $action->forcedDataType = $this->userDataType->name;

        $this->assertTrue($action->shouldActionDisplayOnDataType());
    }

    /**
     * This test checks that `shouldActionDisplayOnDataType` method returns false
     * if the action should only be displayed for a specific data type.
     */
    public function testFalseIsReturnedIfDataTypeDoesNotMatchesTheOneWhereTheActionWasCreatedFor()
    {
        $action = new StubAction($this->userDataType, $this->user);
        $action->forcedDataType = 'not users'; // different data type

        $this->assertFalse($action->shouldActionDisplayOnDataType());
    }
}

class StubAction extends AbstractAction
{
    public bool $defaultRouteResult = false;
    public bool $customRouteResult = false;
    public array $attributesResult = [];
    public ?string $forcedDataType = null;

    public function getTitle()
    {
        return 'Test';
    }

    public function getIcon()
    {
        return 'voyager-test';
    }

    public function getPolicy()
    {
        return 'browse';
    }

    public function getDefaultRoute()
    {
        return $this->defaultRouteResult;
    }

    public function getCustomRoute()
    {
        return $this->customRouteResult;
    }

    public function getAttributes()
    {
        return $this->attributesResult;
    }

    public function getDataType()
    {
        return $this->forcedDataType;
    }
}
