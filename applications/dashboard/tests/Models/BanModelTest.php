<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Models;

use BanModel;
use CommentModel;
use DiscussionModel;
use Vanilla\Dashboard\Models\UserMentionsModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for BanModel.
 */
class BanModelTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /** @var BanModel */
    private $banModel;

    /** @var CommentModel */
    private $commentModel;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->banModel = $this->container()->get(BanModel::class);
        $this->commentModel = $this->container()->get(CommentModel::class);
    }

    /**
     * Test inserting Discussion Mentions using UserMentionsModel::addDiscussionMention().
     */
    public function testAddNewBanRuleSuccess()
    {
        for ($i = 0; $i < 100; $i++) {
            $this->createUser(["email" => "testUser{$i}@higherlogic.com"]);
        }
        $banRule = ["BanType" => "Email", "BanValue" => "%@higherlogic.com", "Notes" => "Testing Ban"];
        $banRule = $this->banModel->save($banRule);
        $banData = $this->banModel->getID($banRule["BanID"], DATASET_TYPE_ARRAY);
        $numberOfBannedUsers = $this->userModel->getCount(["Banned>" => 0]);

        $this->assertEquals($banData["CountUsers"], $numberOfBannedUsers);
        $this->assertEquals(100, $numberOfBannedUsers);

        $this->banModel->delete(["BanID" => $banRule["BanID"]]);
        // Make sure deleting ban rule unbans all of the users previously banned by this rule.
        $numberOfBannedUsers = $this->userModel->getCount(["Banned>" => 0]);
        $this->assertEquals(0, $numberOfBannedUsers);

        $banRule = ["BanType" => "IPAddress", "BanValue" => "1.2.3%", "Notes" => "Testing Ban"];
        $banRule = $this->banModel->save($banRule);
        $banData = $this->banModel->getID($banRule["BanID"], DATASET_TYPE_ARRAY);
        $numberOfBannedUsers = $this->userModel->getCount(["Banned>" => 0]);

        $this->assertEquals($banData["CountUsers"], $numberOfBannedUsers);
        $this->assertEquals(100, $numberOfBannedUsers);

        $banRule["BanValue"] = "1.2.4%";
        $banRule = $this->banModel->save($banRule);
        $banData = $this->banModel->getID($banRule["BanID"], DATASET_TYPE_ARRAY);
        // Make sure deleting ban rule unbans all of the users previously banned by this rule.
        $numberOfBannedUsers = $this->userModel->getCount(["Banned>" => 0]);
        $this->assertEquals($banData["CountUsers"], $numberOfBannedUsers);
        $this->assertEquals(0, $numberOfBannedUsers);
    }
}
