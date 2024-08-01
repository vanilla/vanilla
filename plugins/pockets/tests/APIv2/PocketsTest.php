<?php
/**
 * @author Dani Stark<dani.stark@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

use VanillaTests\APIv2\AbstractResourceTest;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Class PocketsTest
 */
class PocketsTest extends AbstractResourceTest
{
    use CommunityApiTestTrait;

    /** @inheritdoc */
    protected static $addons = ["vanilla", "pockets"];

    /** @var array Test data array */
    protected static $data;

    /** @var string The name of the primary key of the resource. */
    protected $pk = "pocketID";

    /** {@inheritdoc} */
    protected $testPagingOnIndex = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/pockets";

        parent::__construct($name, $data, $dataName);

        $this->editFields = [
            "name",
            "body",
            "repeatType",
            "page",
            "sort",
            "location",
            "format",
            "mobileType",
            "isDashboard",
            "isEmbeddable",
            "isAd",
            "enabled",
        ];
        $this->patchFields = [
            "name",
            "body",
            "repeatType",
            "page",
            "sort",
            "location",
            "format",
            "mobileType",
            "isDashboard",
            "isEmbeddable",
            "isAd",
            "enabled",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo["adminUserID"], false, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row)
    {
        $newRow = [];

        $dt = new \DateTimeImmutable();
        foreach ($this->patchFields as $key) {
            $value = $row[$key];
            if (in_array($key, ["name", "body", "description"])) {
                $value .= " " . $dt->format(\DateTime::RSS);
            } elseif (stripos($key, "id") === strlen($key) - 2) {
                $value++;
            }
            $newRow[$key] = $value;
        }

        return $newRow;
    }

    /**
     * Grab values for inserting a new pocket.
     *
     * @param string $name Name of the pocket
     * @return array
     */
    public function record(string $name = "Test Pocket"): array
    {
        $record = $this->getPocketTemplate();
        $record["name"] = $name;
        foreach ($record as $key => $value) {
            if ($value === "" || $value === null) {
                unset($record[$key]);
            }
        }
        return $record;
    }

    /**
     * Test getting pocket by id.
     *
     * @depends testPostPocket
     */
    public function testGet(): void
    {
        $result = $this->api()->get("/pockets/" . self::$data["pocketID"]);
        $this->assertEquals(200, $result->getStatusCode());
        $resultBody = $result->getBody();
        $expandedBodyExists = isset($resultBody["body"]);
        $this->assertEquals(false, $expandedBodyExists);

        $resultExpand = $this->api()->get("/pockets/" . self::$data["pocketID"] . "?expand=body");
        $this->assertEquals(200, $resultExpand->getStatusCode());
        $resultBody = $resultExpand->getBody();
        $expandedBodyExists = isset($resultBody["body"]);
        $this->assertEquals(true, $expandedBodyExists);
    }

    /**
     * Provide pocket test data.
     *
     * @return array
     */
    public function providePocketFields(): array
    {
        $r = [
            //name/body/widgetID/repeatType/repeatEvery/repeastIndexes/mobileType/roleIDs/isEmbeddable/isAd/page/enabled/
            //isDashboard/location/categoryID/includeChildCategories/format/roleIDs
            "pocket-widget" => [["body" => "<html>\n    <body>\n        test pocket\n    </body>\n</html>"], null],
            "repeatType-every-success" => [["repeatType" => "every", "repeatEvery" => "1"], null],
            "repeatType-every-failure" => [["repeatType" => "every"], 400],
            "repeatType-index-success" => [["repeatType" => "index", "repeatIndexes" => [1, 2]], null],
            "repeatType-index-failure" => [["repeatType" => "index"], 400],
            "repeatType-after" => [["repeatType" => "after"], null],
            "repeatType-before" => [["repeatType" => "before"], null],
            "repeatType-once" => [["repeatType" => "once"], null],
            "repeatType-fail" => [["repeatType" => "wrongType"], 422],
            "mobileType-only" => [["mobileType" => "only"], null],
            "mobileType-never" => [["mobileType" => "never"], null],
            "mobileType-default" => [["mobileType" => "default"], null],
            "mobileType-fail" => [["mobileType" => "wrongType"], 422],
            "isEmbeddable-true" => [["isEmbeddable" => true], null],
            "isAd-true" => [["isAd" => true], null],
            "page-activity" => [["page" => "activity"], null],
            "page-categories" => [["page" => "categories"], null],
            "page-discussions" => [["page" => "discussions"], null],
            "page-home" => [["page" => "home"], null],
            "page-inbox" => [["page" => "inbox"], null],
            "page-profile" => [["page" => "profile"], null],
            "page-invalid" => [["page" => "invalid"], 422],
            "pocket-enabled" => [["enabled" => true], null],
            "isDashboard-true" => [["isDashboard" => true], null],
            "location-betweenDiscussions" => [["location" => "BetweenDiscussions"], null],
            "location-content" => [["location" => "Content"], null],
            "location-betweenComments" => [["location" => "BetweenComments"], null],
            "location-head" => [["location" => "Head"], null],
            "location-foot" => [["location" => "Foot"], null],
            "location-custom" => [["location" => "Custom"], null],
            "location-fail" => [["location" => "wrongLocation"], 422],
            "pocket-roleIDs" => [["roleIDs" => "roleIDs"], null],

            //TODO SiteHome available with subcommunity
        ];
        return $r;
    }

    /**
     * Pocket template.
     *
     * @return array
     */
    protected function getPocketTemplate(): array
    {
        $rd = self::id("pocketTemplate");
        return [
            "name" => $rd . "pocketApiTest",
            "body" => "pocketApiTest",
            "widgetID" => null,
            "repeatType" => "once",
            "repeatEvery" => "",
            "repeatIndexes" => null,
            "mobileType" => "never",
            "roleIDs" => "",
            "isEmbeddable" => false,
            "isAd" => false,
            "page" => "home",
            "enabled" => false,
            "isDashboard" => false,
            "sort" => 0,
            "location" => "Panel",
            "categoryID" => null,
            "includeChildCategories" => null,
            "format" => "raw",
        ];
    }

    /**
     * Prepare pockets data.
     *
     * @param array $fields
     * @param int|null $failCode
     */
    protected function preparePockets(array $fields, $failCode)
    {
        if (array_key_exists("roleIDs", $fields)) {
            $roles = RoleModel::roles();
            $roleIds = array_column($roles, "RoleID");
            self::$data["roleIDs"] = $roleIds;
            $fields["roleIDs"] = $roleIds;
        }
        $pocketTemplate = $this->getPocketTemplate();
        //Update template with provider fields.
        foreach ($fields as $key => $value) {
            $pocketTemplate[$key] = $value;
        }

        foreach ($pocketTemplate as $key => $value) {
            if ($value === "" || $value === null) {
                unset($pocketTemplate[$key]);
            }
        }
        try {
            $result = $this->api()->post("/pockets", $pocketTemplate);
            if ($result->getBody()["pocketID"] && !isset(self::$data["pocketID"])) {
                self::$data["pocketID"] = $result->getBody()["pocketID"];
            }
            if (empty($failCode)) {
                $this->assertEquals(201, $result->getStatusCode());
            }
        } catch (Exception $e) {
            $this->assertEquals($e->getCode(), $failCode);
        }
    }

    /**
     * Test Posting pockets.
     *
     * @param array $fields
     * @param int|null $fail
     *
     * @dataProvider providePocketFields
     */
    public function testPostPocket(array $fields, ?int $fail)
    {
        $this->preparePockets($fields, $fail);
    }

    /**
     * Test deleting a pocket.
     *
     * @depends testPostPocket
     */
    public function testDelete()
    {
        $result = $this->api()->delete("/pockets/" . self::$data["pocketID"]);
        $this->assertEquals(204, $result->getStatusCode());
        $this->expectExceptionMessage("Pocket Not Found");
        $this->api()->get("/pockets/" . self::$data["pocketID"]);
    }

    /**
     * {@inheritdoc}
     * @requires function MessagesApiController::patch
     */
    public function testEditFormatCompat(string $editSuffix = "/edit")
    {
        $this->fail(__METHOD__ . " needs to be implemented");
    }

    /**
     * We don't need the image.
     */
    public function testMainImageField()
    {
        $this->markTestSkipped();
    }
}
