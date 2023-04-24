<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\SiteTestCase;

/**
 * Tests for the UserMetaModel.
 */
class UserMetaModelTest extends SiteTestCase
{
    /** @var \UserMetaModel */
    private $userMetaModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCaching();
        $this->resetTable("UserMeta");
        // Don't use the shared one from the container.
        $this->userMetaModel = new \UserMetaModel();
        $this->createUserFixtures();
    }

    /**
     * Test that we can set and fetch a single value.
     */
    public function testSetGetSingleValue()
    {
        $this->userMetaModel->setUserMeta($this->memberID, "MyMeta", "Hello");
        $value = $this->userMetaModel->getUserMeta($this->memberID, "MyMeta");
        $this->assertEquals(["MyMeta" => "Hello"], $value);

        // Make sure caching works.
        $this->resetTable("UserMeta", false);
        $value = $this->userMetaModel->getUserMeta($this->memberID, "MyMeta");
        $this->assertEquals(["MyMeta" => "Hello"], $value);
    }

    /**
     * Test that we can fetch wildcard values.
     */
    public function testGetWildcard()
    {
        $this->userMetaModel->setUserMeta($this->memberID, "Root.Key1", "val1");
        $this->userMetaModel->setUserMeta($this->memberID, "Root.Key2", "val2");
        $this->userMetaModel->setUserMeta($this->memberID, "RootButNotReally", "ignored");
        $actual = $this->userMetaModel->getUserMeta($this->memberID, "Root.%");
        $this->assertEquals(
            [
                "Root.Key1" => "val1",
                "Root.Key2" => "val2",
            ],
            $actual
        );
        // And we can trim prefixes
        $actual = $this->userMetaModel->getUserMeta($this->memberID, "Root.%", [], "Root.");
        $this->assertEquals(
            [
                "Key1" => "val1",
                "Key2" => "val2",
            ],
            $actual
        );

        // Caching also works for this.
        $this->resetTable("UserMeta", false);
        $actual = $this->userMetaModel->getUserMeta($this->memberID, "Root.%", [], "Root.");
        $this->assertEquals(
            [
                "Key1" => "val1",
                "Key2" => "val2",
            ],
            $actual
        );
    }

    /**
     * Test that we can get a default value for one or multiple users.
     */
    public function testGetDefault()
    {
        $expected = [
            "MyKey" => "DefaultVal",
        ];
        // With a single user.
        $actual = $this->userMetaModel->getUserMeta($this->adminID, "MyKey", "DefaultVal");
        $this->assertSame($expected, $actual);
        // With multiple users.
        $actual = $this->userMetaModel->getUserMeta([$this->adminID, $this->memberID], "MyKey", "DefaultVal");
        $this->assertSame(
            [
                $this->adminID => $expected,
                $this->memberID => $expected,
            ],
            $actual
        );
    }

    /**
     * Test that array values are stored and returned properly.
     */
    public function testGetMultiValueMeta()
    {
        // Some meta values may be arrays. We should be able to fetch these too.
        $expected = ["val1", "val2", "val3"];
        $this->userMetaModel->setUserMeta($this->adminID, "MyArr", $expected);
        $actual = $this->userMetaModel->getUserMeta($this->adminID, "MyArr");
        $this->assertEquals($expected, $actual["MyArr"]);

        // Items can be removed.
        $expected = ["val4", "val5"];
        $this->userMetaModel->setUserMeta($this->adminID, "MyArr", $expected);
        $actual = $this->userMetaModel->getUserMeta($this->adminID, "MyArr");
        $this->assertEquals($expected, $actual["MyArr"]);
    }

    /**
     * Test that setting a single value, invalidates all wildcard caches it may be a part of.
     */
    public function testSetInvalidateWildcardCache()
    {
        $this->userMetaModel->setUserMeta($this->adminID, "Root.Key1", "val1");
        $this->userMetaModel->setUserMeta($this->adminID, "Root.Key2", "val2");
        // Get the value and fill the cache, we've already tested in previous tests that the cache is working
        // in this test suite.
        $actual = $this->userMetaModel->getUserMeta($this->adminID, "Root.%");
        $this->assertEquals(
            [
                "Root.Key1" => "val1",
                "Root.Key2" => "val2",
            ],
            $actual
        );

        // Now set just one of the values and the cache should have been cleared.
        $this->userMetaModel->setUserMeta($this->adminID, "Root.Key1", "val1.1");
        $actual = $this->userMetaModel->getUserMeta($this->adminID, "Root.%");
        $this->assertEquals(
            [
                "Root.Key1" => "val1.1",
                "Root.Key2" => "val2",
            ],
            $actual
        );
    }
}
