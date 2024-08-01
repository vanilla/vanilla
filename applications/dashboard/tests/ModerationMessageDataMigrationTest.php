<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use Gdn;
use MessageModel;

/**
 * Test the structure changes to the Message table.
 */
class ModerationMessageDataMigrationTest extends \VanillaTests\SiteTestCase
{
    /**
     * Test that the relevant data is migrated correctly.
     */
    public function testDataMigration()
    {
        $structure = Gdn::structure();
        //Set up the table the old way.
        $structure
            ->table("Message")
            ->column("Application", "varchar(255)", true)
            ->column("Controller", "varchar(255)", true)
            ->column("Method", "varchar(255)", true)
            ->column("CategoryID", "int", true)
            ->column("CssClass", "varchar(20)", true)
            ->set();
        $structure->table("Message")->dropColumn("LayoutViewType");
        $structure->table("Message")->dropColumn("RecordID");
        $structure->table("Message")->dropColumn("RecordType");
        $structure->table("Message")->dropColumn("Type");

        $messageModel = Gdn::getContainer()->get(MessageModel::class);

        // Save the data the old way.
        $legacyLayoutViews = $messageModel->getLegacyLayoutViews();
        foreach ($legacyLayoutViews as $view) {
            $oldViewType = $view->getLegacyType();
            $oldViewArray = explode("/", $oldViewType);
            [$application, $controller, $method] = $oldViewArray;
            Gdn::sql()->insert("Message", [
                "AllowDismiss" => 0,
                "Content" => $oldViewType,
                "Enabled" => 1,
                "Format" => "text",
                "Application" => $application,
                "Controller" => $controller,
                "Method" => $method,
                "AssetTarget" => "Content",
                "CssClass" => "InfoMessage",
            ]);
        }

        // Run structure.
        include PATH_APPLICATIONS . "/dashboard/settings/structure.php";

        // Verify that the data transferred correctly.
        $messages = $messageModel->getMessages();
        $legacyMap = $messageModel->getLocationMap();
        foreach ($messages as $message) {
            $this->assertSame($legacyMap[$message["Content"]], $message["LayoutViewType"]);
            $this->assertSame($message["Type"], "info");
        }
    }
}
