<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Some basic tests for the `UserModel`.
 */
class UserModelExpandUserFragmentTest extends SiteTestCase
{
    use ExpectExceptionTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * @var \UserModel
     */
    private $model;

    const VALID_FRAGMENT_FIELDS = ["userID", "name", "photoUrl", "dateLastActive"];

    const INVALID_FRAGMENT_FIELDS = [
        "email",
        "password",
        "hashMethod",
        "updateIPAddress",
        "insertIPAddress",
        "lastIPAddress",
        "permissions",
    ];

    /**
     * Get a new model for each test.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->model = $this->container()->get(\UserModel::class);
    }

    /**
     * Test UserModel->expandUsers method
     */
    public function testExpandUsers()
    {
        $name = uniqid("User");
        $user = [
            "name" => $name,
            "email" => $name . "@mail.com",
            "Password" => "test",
            "HashMethod" => "text",
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateLastActive" => date("Y-m-d H:i:s"),
        ];
        $userID = (int) $this->model->SQL->insert($this->model->Name, $user);
        $this->assertGreaterThan(0, $userID);

        $userRecord = $this->model->getByUsername($name);
        $this->assertEquals($userID, $userRecord->UserID);

        $rows = [["insertUser" => $userID], ["insertUser" => $userID * 1000]];
        $this->model->expandUsers($rows, ["insertUser"]);
        $this->assertEquals(2, count($rows));
        $both = 0;
        foreach ($rows as $row) {
            foreach (self::VALID_FRAGMENT_FIELDS as $validField) {
                $this->assertArrayHasKey($validField, $row["insertUser"]);
            }
            foreach (self::INVALID_FRAGMENT_FIELDS as $invalidField) {
                $this->assertArrayNotHasKey($invalidField, $row["insertUser"]);
            }
            if ($row["insertUser"]["userID"] === $userID) {
                //existing user
                $this->assertEquals($name, $row["insertUser"]["name"]);
                $both += 10;
            } else {
                //unknown user
                $this->assertEquals(\UserModel::GENERATED_FRAGMENT_KEY_UNKNOWN, $row["insertUser"]["name"]);
                $both += 1;
            }
        }
        $this->assertEquals(11, $both);
    }

    /**
     * Test that empty username are replaced by 'Unknown' by UserModel::expandUsers.
     */
    public function testExpandUserNoUserName()
    {
        $user = [
            "name" => "",
            "email" => "noUserName@mail.com",
            "Password" => "test",
            "HashMethod" => "text",
            "DateInserted" => date("Y-m-d H:i:s"),
        ];
        $userID = (int) $this->model->SQL->insert($this->model->Name, $user);
        $row = ["insertUser" => $userID];
        $this->model->expandUsers($row, ["insertUser"]);
        $this->assertEquals("Unknown", $row["insertUser"]["name"]);
    }

    /**
     * Test a case where we have corrupted usermeta rows for the title field that now only allows a single value.
     *
     * Other profile fields have their own tests, but this one has special handling for legacy reasons.
     */
    public function testExpandUserCorruptedTitle()
    {
        $user = $this->createUser();
        // When using the users APIs this can't happen anymore.
        // However some dbs have duped records.
        \Gdn::userMetaModel()->insert([
            "UserID" => $user["userID"],
            "Name" => "Profile.Title",
            "Value" => "Title 1",
            "QueryValue" => "Profile.Title.Title 1",
        ]);
        \Gdn::userMetaModel()->insert([
            "UserID" => $user["userID"],
            "Name" => "Profile.Title",
            "Value" => "Title 2",
            "QueryValue" => "Profile.Title.Title 2",
        ]);
        \Gdn::cache()->flush();

        $row = $this->api()
            ->get("/users/{$user["userID"]}", ["expand" => "all"])
            ->getBody();
        $this->assertEquals("Title 2", $row["user"]["title"]);
    }

    /**
     * Test UserModel->getFragmnetById method
     */
    public function testGetFragmentById()
    {
        $name = uniqid("User");
        $user = [
            "name" => $name,
            "email" => $name . "@mail.com",
            "Password" => "test",
            "HashMethod" => "text",
            "DateInserted" => date("Y-m-d H:i:s"),
            "DateLastActive" => date("Y-m-d H:i:s"),
        ];
        $userID = (int) $this->model->SQL->insert($this->model->Name, $user);
        $this->assertGreaterThan(0, $userID);

        // existing user fragment
        $userRecord = $this->model->getByUsername($name);
        $this->assertEquals($userID, $userRecord->UserID);

        $row = $this->model->getFragmentByID($userID);

        foreach (self::VALID_FRAGMENT_FIELDS as $validField) {
            $this->assertArrayHasKey($validField, $row);
        }
        foreach (self::INVALID_FRAGMENT_FIELDS as $invalidField) {
            $this->assertArrayNotHasKey($invalidField, $row);
        }
        $this->assertEquals($userID, $row["userID"]);
        $this->assertEquals($name, $row["name"]);

        // unknown user fragment
        $row = $this->model->getFragmentByID(0, true);

        foreach (self::VALID_FRAGMENT_FIELDS as $validField) {
            $this->assertArrayHasKey($validField, $row);
        }
        foreach (self::INVALID_FRAGMENT_FIELDS as $invalidField) {
            $this->assertArrayNotHasKey($invalidField, $row);
        }
        $this->assertEquals(\UserModel::UNKNOWN_USER_ID, $row["userID"]);
        $this->assertEquals(\UserModel::GENERATED_FRAGMENT_KEY_UNKNOWN, $row["name"]);
    }
}
