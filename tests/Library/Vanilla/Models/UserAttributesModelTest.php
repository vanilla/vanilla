<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\Models\UserAttributesModel;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `UserAttributesModel` class.
 */
class UserAttributesModelTest extends SiteTestCase {
    /**
     * @var UserAttributesModel
     */
    private $model;

    /**
     * {@inheritDoc}
     */
    public static function getAddons(): array {
        return ['dashboard'];
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->container()->call(function (UserAttributesModel $model) {
            $this->model = $model;
        });
        $this->createUserFixtures();
    }

    /**
     * The user attributes should handle a basic get/set.
     *
     * @param mixed $value
     * @dataProvider provideValues
     */
    public function testGetSetValue($value): void {
        $this->model->setAttributes($this->memberID, __FUNCTION__, $value);
        $this->assertSame($value, $this->model->getAttributes($this->memberID, __FUNCTION__, $value));
    }

    /**
     * Provide some test values.
     *
     * @return array
     */
    public function provideValues(): array {
        return [
            'true' => ['true'],
            'false' => ['false'],
            'string' => ['foo'],
            'array' => [['foo' => 'bar']],
        ];
    }

    /**
     * The default value should be returned if the row doesn't exist.
     */
    public function testGetDefault(): void {
        $v = 'a';
        $this->assertSame($v, $this->model->getAttributes($this->memberID, __FUNCTION__, $v));
    }

    /**
     * A patch should keep original keys in place.
     */
    public function testPatch(): void {
        $value = ['foo' => 'bar', 'b' => 'c'];
        $this->model->setAttributes($this->memberID, __FUNCTION__, $value);
        $patch = ['bar' => 'baz', 'b' => 'd'];
        $this->model->patchAttributes($this->memberID, __FUNCTION__, $patch);
        $this->assertArraySubsetRecursive(array_replace($value, $patch), $this->model->getAttributes($this->memberID, __FUNCTION__));
    }

    /**
     * You can't patch a non-array.
     */
    public function testPatchInvalid(): void {
        $this->model->setAttributes($this->memberID, __FUNCTION__, 'foo');
        $this->expectException(\InvalidArgumentException::class);
        $this->model->patchAttributes($this->memberID, __FUNCTION__, ['a' => 'b']);
    }

    /**
     * You should be able to set different values for different users.
     */
    public function testGetSetDifferentUsers(): void {
        $users = [$this->memberID, $this->adminID];
        foreach ($users as $userID) {
            $this->model->setAttributes($userID, __FUNCTION__, $userID);
        }
        foreach ($users as $userID) {
            $this->assertSame($userID, $this->model->getAttributes($userID, __FUNCTION__));
        }
    }
}
