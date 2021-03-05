<?php
/**
 * @author Dani Stark<dani.stark@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Addons\Pockets\PocketsModel;
use VanillaTests\APIv2\AbstractAPIv2Test;
use Vanilla\Subcommunities\Tests\SubcommunityApiTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Class PocketsTest
 */
class PocketsTest extends AbstractAPIv2Test {
    use SubcommunityApiTestTrait;
    use CommunityApiTestTrait;

    /** @var array Test data array */
    private static $data;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'subcommunities', 'pockets'];
        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);
    }


    /**
     * Provide pocket test data.
     *
     * @return array
     */
    public function providePocketFields(): array {
        $r = [
            //name/body/widgetID/repeatType/repeatEvery/repeastIndexes/mobileType/roleIDs/isEmbeddable/isAd/page/enabled/
            //isDashboard/location/categoryID/includeChildCategories/format/roleIDs
            'pocket-widget' => [["body" => "<html>\n    <body>\n        test pocket\n    </body>\n</html>"], null],
            'repeatType-every-success' => [['repeatType' => "every", "repeatEvery" => "1" ], null],
            'repeatType-every-failure' => [['repeatType' => "every"], 400],
            'repeatType-index-success' => [['repeatType' => "index", "repeatIndexes" => [1,2]], null],
            'repeatType-index-failure' => [['repeatType' => "index"], 400],
            'repeatType-after' => [['repeatType' => "after"], null],
            'repeatType-before' => [['repeatType' => "before"], null],
            'repeatType-once' => [['repeatType' => "once"], null],
            'repeatType-fail' => [['repeatType' => "wrongType"], 422],
            'mobileType-only' => [['mobileType'=> 'only'], null],
            'mobileType-never' => [['mobileType'=> 'never'], null],
            'mobileType-default' => [['mobileType'=> 'default'], null],
            'mobileType-fail' => [['mobileType'=> 'wrongType'], 422],
            'isEmbeddable-true' => [['isEmbeddable'=> true], null],
            'isAd-true' => [['isAd'=> true], null],
            'page-activity' => [['page'=> 'activity'], null],
            'page-categories' => [['page'=> 'categories'], null],
            'page-discussions' => [['page'=> 'discussions'], null],
            'page-home' => [['page'=> 'home'], null],
            'page-inbox' => [['page'=> 'inbox'], null],
            'page-profile' => [['page'=> 'profile'], null],
            'page-invalid' => [['page'=> 'invalid'], 422],
            'pocket-enabled' => [['enabled'=> true], null],
            'isDashboard-true' => [['isDashboard'=> true], null],
            'location-betweenDiscussions' => [['location'=> 'BetweenDiscussions'], null],
            'location-content' => [['location'=> 'Content'], null],
            'location-betweenComments' => [['location'=> 'BetweenComments'], null],
            'location-head' => [['location'=> 'Head'], null],
            'location-foot' => [['location'=> 'Foot'], null],
            'location-custom' => [['location'=> 'Custom'], null],
            'location-fail' => [['location'=> 'wrongLocation'], 422],
            'pocket-subcommunityIDs' => [['subcommunityIDs'=> ['Sub1', 'Sub2']], null],
            'pocket-roleIDs' => [['roleIDs'=> 'roleIDs'], null]


           //TODO SiteHome avialable with subc

        ];
        return $r;
    }

    /**
     * Pocket template.
     *
     * @return array
     */
    private function getPocketTemplate(): array {
        $rd = rand(10, 1000);
        return  [
            'name' => $rd.'pocketApiTest',
            'body' => 'pocketApiTest',
            'widgetID' => null,
            'repeatType' => 'once',
            'repeatEvery' => '',
            'repeatIndexes' => null,
            'mobileType' => '',
            'roleIDs' => '',
            'isEmbeddable' => 0,
            'isAd' => 0,
            'page' => 'home',
            'enabled' => 0,
            'isDashboard' => 0,
            'sort' => null,
            'location' => '',
            'categoryID' => null,
            'includeChildCategories' => null,
            'format' => 'raw',
            'subcommunityIDs' => null
        ];
    }

    /**
     * Prepare pockets data.
     *
     * @param array $fields
     * @param int|null $failCode
     */
    private function preparePockets(array $fields, $failCode) {
        if (array_key_exists('subcommunityIDs', $fields)) {
            foreach ($fields['subcommunityIDs'] as $subCommKey) {
                if (!array_key_exists($subCommKey, self::$data['subcommunity'] ?? [])) {
                    self::$data['subcommunity'][$subCommKey] = $this->createSubcommunity(['Name' => $subCommKey.' Community Sub 1']);
                }
            }
            $realIds = [];
            foreach ($fields['subcommunityIDs'] as $subCommKey) {
                $realIds[] = self::$data['subcommunity'][$subCommKey]['subcommunityID'];
            }
            $fields['subcommunityIDs'] = $realIds;
        }
        if (array_key_exists('roleIDs', $fields)) {
            $roles = RoleModel::roles();
            $roleIds = array_column($roles, 'RoleID');
            self::$data['roleIDs'] = $roleIds;
            $fields['roleIDs'] = $roleIds;
        }
        $pocketTemplate = $this->getPocketTemplate();
        //Update template with provider fields.
        foreach ($fields as $key => $value) {
            $pocketTemplate[$key] =   $value;
        }

        foreach ($pocketTemplate as $key => $value) {
            if ($value === '' || $value === null) {
                unset($pocketTemplate[$key]);
            }
        }
        try {
            $result = $this->api()->post("/pockets", $pocketTemplate);
            if ($result->getBody()['pocketID'] && !isset(self::$data['pocketID'])) {
                self::$data['pocketID'] = $result->getBody()['pocketID'];
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
    public function testPostPocket(array $fields, ?int $fail) {
        $this->preparePockets($fields, $fail);
    }

    /**
     * Test Get all pockets.
     *
     * @depends testPostPocket
     */
    public function testPocketIndex(): void {
        $result = $this->api()->get("/pockets");
        $this->assertEquals(200, $result->getStatusCode());
        //add user as member to secret group.
        $pocketModel = new PocketsModel();
        $modelCount = $pocketModel->getTotalRowCount();
        $resultCount = count($result->getBody());
        $this->assertEquals($modelCount, $resultCount);
    }

    /**
     * Test getting pocket by id.
     *
     * @depends testPostPocket
     */
    public function testGetPocketID(): void {
        $result = $this->api()->get("/pockets/".self::$data['pocketID']);
        $this->assertEquals(200, $result->getStatusCode());
        $resultBody = $result->getBody();
        $expandedBodyExists = isset($resultBody['body']);
        $this->assertEquals(false, $expandedBodyExists);

        $resultExpand = $this->api()->get("/pockets/".self::$data['pocketID']."?expand=body");
        $this->assertEquals(200, $resultExpand->getStatusCode());
        $resultBody = $resultExpand->getBody();
        $expandedBodyExists = isset($resultBody['body']);
        $this->assertEquals(true, $expandedBodyExists);
    }

    /**
     * Test getting pocket for editing.
     *
     * @depends testPostPocket
     */
    public function testGetEditPocket(): void {
        $result = $this->api()->get("/pockets/".self::$data['pocketID']."/edit");
        $this->assertEquals(200, $result->getStatusCode());
    }

    /**
     * Test enabling a pocket.
     *
     * @depends testPostPocket
     */
    public function testEnableDisablePocket() {
        //Enable Pocket
        $result = $this->api()->patch("/pockets/".self::$data['pocketID']."/status", ['enable' => true]);
        $this->assertEquals(200, $result->getStatusCode());
        $result = $this->api()->get("/pockets/".self::$data['pocketID'])->getBody();
        $this->assertEquals(0, $result['disabled']);
        //Disable Pocket
        $result = $this->api()->patch("/pockets/".self::$data['pocketID']."/status", ['enable' => false]);
        $this->assertEquals(200, $result->getStatusCode());
        $result = $this->api()->get("/pockets/".self::$data['pocketID'])->getBody();
        $this->assertEquals(1, $result['disabled']);
    }

    /**
     * Test pocket patch,
     *
     * @depends testPostPocket
     */
    public function testPocketPatchTest() {
        $body = [
            'name' => 'updatedPocket',
            'isDashboard' => true,
            'isEmbeddable' => true,
            'isAd' => true,
            'enabled' => true
        ];
        $result = $this->api()->patch("/pockets/".self::$data['pocketID'], $body)->getBody();
        $this->assertEquals($body['name'], $result['name']);
        $this->assertEquals('ad', $result['type']);
        $this->assertEquals($result['embeddedNever'], 0);
        $this->assertEquals($result['showInDashboard'], 1);
        $this->assertEquals($result['disabled'], 0);
    }

    /**
     * Test deleting a pocket.
     *
     * @depends testPostPocket
     */
    public function testDeletePocket() {
        $result = $this->api()->delete("/pockets/".self::$data['pocketID']);
        $this->assertEquals(204, $result->getStatusCode());
        $this->expectExceptionMessage("Pocket Not Found");
        $this->api()->get("/pockets/".self::$data['pocketID']);
    }
}
