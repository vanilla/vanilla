<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\ExpectErrorTrait;
use VanillaTests\SiteTestTrait;

/**
 * Some basic tests for the `UserModel`.
 */
class UserModelExpandUserFragmentTest extends TestCase {
    use SiteTestTrait, ExpectErrorTrait;

    /**
     * @var \UserModel
     */
    private $model;

    const VALID_FRAGMENT_FIELDS = [
        'userID',
        'name',
        'photoUrl',
        'dateLastActive'
    ];

    const INVALID_FRAGMENT_FIELDS = [
        'email',
        'password',
        'hashMethod',
        'updateIPAddress',
        'insertIPAddress',
        'lastIPAddress',
        'permissions'
    ];

    /**
     * Get a new model for each test.
     */
    protected function setUp() {
        parent::setUp();

        $this->model = $this->container()->get(\UserModel::class);
    }

    /**
     * Test UserModel->expandUsers method
     */
    public function testExpandUsers() {
        $name = uniqid('User');
        $user = [
            'name' => $name,
            'email' => $name.'@mail.com',
            'Password' => 'test',
            'HashMethod' => 'text',
            'DateInserted' => date("Y-m-d H:i:s")
        ];
        $userID = (int)$this->model->SQL->insert($this->model->Name, $user);
        $this->assertGreaterThan(0, $userID);

        $userRecord = $this->model->getByUsername($name);
        $this->assertEquals($userID, $userRecord->UserID);

        $rows = [
            ['insertUser' => $userID],
            ['insertUser' => $userID*1000],
        ];
        $this->model->expandUsers($rows, ['insertUser']);
        $this->assertEquals(2, count($rows));
        $both = 0;
        foreach ($rows as $row) {
            foreach (self::VALID_FRAGMENT_FIELDS as $validField) {
                $this->assertArrayHasKey($validField, $row['insertUser']);
            }
            foreach (self::INVALID_FRAGMENT_FIELDS as $invalidField) {
                $this->assertArrayNotHasKey($invalidField, $row['insertUser']);
            }
            if ($row['insertUser']['userID'] === $userID) {
                //existing user
                $this->assertEquals($name, $row['insertUser']['name']);
                $both += 10;
            } else {
                //unknown user
                $this->assertEquals(\UserModel::GENERATED_FRAGMENT_KEY_UNKNOWN, $row['insertUser']['name']);
                $both += 1;
            }
        }
        $this->assertEquals(11, $both);
    }

    /**
     * Test UserModel->getFragmnetById method
     */
    public function testGetFragmentById() {
        $name = uniqid('User');
        $user = [
            'name' => $name,
            'email' => $name.'@mail.com',
            'Password' => 'test',
            'HashMethod' => 'text',
            'DateInserted' => date("Y-m-d H:i:s")
        ];
        $userID = (int)$this->model->SQL->insert($this->model->Name, $user);
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
        $this->assertEquals($userID, $row['userID']);
        $this->assertEquals($name, $row['name']);

        // unknown user fragment
        $row = $this->model->getFragmentByID(0, true);

        foreach (self::VALID_FRAGMENT_FIELDS as $validField) {
            $this->assertArrayHasKey($validField, $row);
        }
        foreach (self::INVALID_FRAGMENT_FIELDS as $invalidField) {
            $this->assertArrayNotHasKey($invalidField, $row);
        }
        $this->assertEquals(\UserModel::UNKNOWN_USER_ID, $row['userID']);
        $this->assertEquals(\UserModel::GENERATED_FRAGMENT_KEY_UNKNOWN, $row['name']);
    }
}
