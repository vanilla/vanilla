<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Addons\Pockets;

use Vanilla\Addons\Pockets\PocketsModel;
use Vanilla\Widgets\AbstractWidgetModule;
use VanillaTests\APIv0\TestDispatcher;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\MockWidgets\MockWidget1;
use VanillaTests\Fixtures\MockWidgets\MockWidget2;

/**
 * Tests for pocket rendering.
 */
class PocketsRenderTest extends AbstractAPIv2Test
{
    use EventSpyTestTrait;
    public static $addons = ["vanilla", "pockets"];

    /**
     * @var null|string
     */
    private static $currentOnPage = null;

    /** @var PocketsModel */
    private $pocketsModel;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$currentOnPage = null;
        $this->pocketsModel = $this->container()->get(PocketsModel::class);
        $this->resetTable("Pocket");
        \PocketsPlugin::instance()->resetState();
    }

    /**
     * Test rendering of the pockets.
     */
    public function testRenderPockets()
    {
        $this->pocketsModel->touchPocket("HTML Pocket", [
            "Body" => '<div id="htmlpocket">hello custom</div>',
            "Disabled" => \Pocket::ENABLED,
        ]);
        $this->pocketsModel->touchPocket("Widget Pocket", [
            "WidgetParameters" => ["name" => "My Widget 1"],
            "Format" => PocketsModel::FORMAT_WIDGET,
            "WidgetID" => MockWidget1::getWidgetID(),
            "Disabled" => \Pocket::ENABLED,
        ]);

        self::$currentOnPage = "discussions";
        $html = $this->bessy()->getHtml("/discussions", [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorText("#htmlpocket", "hello custom");
        $html->assertCssSelectorText(".mockWidget", "My Widget 1");
        $countItems = $html->queryCssSelector(".mockWidget")->count();
        $this->assertEquals(1, $countItems);
    }

    /**
     * Test rendering of a pocket for a user with a non-default guest-type role.
     */
    public function testRenderPocketForGuestTypeUser()
    {
        $guestRoleID = $this->roleModel->save([
            "CanSession" => false,
            "Deletable" => true,
            "Description" => "test guest user",
            "Name" => "GuestType",
            "Type" => "guest",
        ]);
        $memberRoleID = $this->roleModel->save([
            "CanSession" => true,
            "Deletable" => true,
            "Description" => "test member user",
            "Name" => "MemberType",
            "Type" => "member",
        ]);
        $memberTypeUserID = $this->userModel->save([
            "Name" => "GuestUser",
            "Email" => "guestuser" . "@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            "RoleID" => $memberRoleID,
        ]);

        $this->pocketsModel->touchPocket("GuestType Pocket", [
            "Body" => '<div id="guesttypepocket">hello guest-type person</div>',
            "Disabled" => \Pocket::ENABLED,
            "RoleIDs" => [$guestRoleID],
        ]);

        // A guest user should see the pocket.
        $session = self::container()->get(\Gdn_Session::class);
        $session->start();

        $html = $this->bessy()->getHtml("/discussions", [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $html->assertCssSelectorText("#guesttypepocket", "hello guest-type person");

        $session->end();

        // A logged-in user should not.

        $this->container()
            ->get(\PocketsPlugin::class)
            ->setUserRoleIDs(null);
        $session->start($memberTypeUserID);

        $html = $this->bessy()->getHtml("/discussions", [], [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]);
        $session->end();
        $html->assertCssSelectorNotTextContains("#guesttypepocket", "hello guest-type person");
    }

    /**
     * Test rendering pockets on specific page.
     *
     * @param string $onPage
     * @param string $notOnPage
     * @dataProvider providePageTests
     */
    public function testRenderOnPagePockets(string $onPage, string $notOnPage)
    {
        $this->pocketsModel->touchPocket("HTML Pocket", [
            "Body" => '<div id="htmlpocket">hello custom on discussions page</div>',
            "Disabled" => \Pocket::ENABLED,
            "Page" => $onPage,
        ]);
        $this->pocketsModel->touchPocket("Widget Pocket", [
            "WidgetParameters" => ["name" => "My Widget 2 on discussions page"],
            "Format" => PocketsModel::FORMAT_WIDGET,
            "WidgetID" => MockWidget2::getWidgetID(),
            "Disabled" => \Pocket::ENABLED,
            "Page" => $onPage,
        ]);

        $htmlPage = $this->bessy()->getHtml(
            "/" . $onPage,
            [],
            [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]
        );
        $htmlPage->assertCssSelectorText("#htmlpocket", "hello custom on discussions page");
        $htmlPage->assertCssSelectorText(".mockWidget", "My Widget 2 on discussions page");
        $htmlOtherPage = $this->bessy()->getHtml(
            "/" . $notOnPage,
            [],
            [TestDispatcher::OPT_DELIVERY_TYPE => DELIVERY_TYPE_ALL]
        );
        $htmlOtherPage->assertCssSelectorNotExists("#htmlpocket");
        $htmlOtherPage->assertCssSelectorNotExists(".mockWidget");
    }

    /**
     * Provide data tests for rendering on page only.
     *
     * @return array
     */
    public function providePageTests(): array
    {
        $r = [["discussions", "categories"], ["categories", "discussions"]];

        return $r;
    }

    /**
     * Event handler to add widget mock.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender)
    {
        if (self::$currentOnPage && $sender->SelfUrl == self::$currentOnPage) {
            $module = new MockWidget1();
            $sender->addModule($module, "Content");
        }
    }
}
