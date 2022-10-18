<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Gdn_Database;

/**
 * Database structure and upgrades for moderation messages.
 */
final class ModerationMessageStructure
{
    /** @var Gdn_Database  */
    private $database;

    /**
     * DI.
     *
     * @param Gdn_Database $database
     */
    public function __construct(Gdn_Database $database)
    {
        $this->database = $database;
    }

    /**
     * Ensure the database table is configured.
     */
    public function structure(): void
    {
        $construct = $this->database->structure();
        $sql = $this->database->sql();
        $construct
            ->table("Message")
            ->primaryKey("MessageID")
            ->column("Content", "text")
            ->column("Format", "varchar(20)", true)
            ->column("AllowDismiss", "tinyint(1)", "1")
            ->column("Enabled", "tinyint(1)", "1")
            ->column("LayoutViewType", "varchar(255)", true)
            ->column("RecordType", "varchar(255)", true)
            ->column("RecordID", "int", true)
            ->column("IncludeSubcategories", "tinyint", "0")
            ->column("AssetTarget", "varchar(20)", true)
            ->column("Type", "varchar(20)", true)
            ->column("Sort", "int", true)
            ->set();

        if ($construct->table("Message")->columnExists("CategoryID")) {
            $sql->update("Message")
                ->set("RecordID", "CategoryID", false, false)
                ->put();
            $sql->update("Message")
                ->set("RecordType", "category")
                ->whereNotIn("CategoryID", [null])
                ->put();

            $construct->table("Message")->dropColumn("CategoryID");
        }

        if (
            $construct->table("Message")->columnExists("Application") &&
            $construct->table("Message")->columnExists("Controller") &&
            $construct->table("Message")->columnExists("Method")
        ) {
            // map insane way of storing location data to a way that makes sense.
            $messageDataMigrationMap = [
                ["ViewType" => "all", "where" => ["Controller" => "[Base]"]],
                ["ViewType" => "all", "where" => ["Controller" => "[NonAdmin]"]],
                [
                    "ViewType" => "profile",
                    "where" => [
                        "Application" => "Dashboard",
                        "Controller" => "Profile",
                        "Method" => "Index",
                    ],
                ],
                [
                    "ViewType" => "discussionList",
                    "where" => [
                        "Application" => "Vanilla",
                        "Controller" => "Discussions",
                        "Method" => "Index",
                    ],
                ],
                [
                    "ViewType" => "categoryList",
                    "where" => [
                        "Application" => "Vanilla",
                        "Controller" => "Categories",
                        "Method" => "Index",
                    ],
                ],
                [
                    "ViewType" => "discussionThread",
                    "where" => [
                        "Application" => "Vanilla",
                        "Controller" => "Discussion",
                        "Method" => "Index",
                    ],
                ],
                [
                    "ViewType" => "newDiscussion",
                    "where" => [
                        "Application" => "Vanilla",
                        "Controller" => "Post",
                        "Method" => "Discussion",
                    ],
                ],
                [
                    "ViewType" => "signin",
                    "where" => [
                        "Application" => "Dashboard",
                        "Controller" => "Entry",
                        "Method" => "Signin",
                    ],
                ],
                [
                    "ViewType" => "registration",
                    "where" => [
                        "Application" => "Dashboard",
                        "Controller" => "Entry",
                        "Method" => "Register",
                    ],
                ],
                [
                    "ViewType" => "newQuestion",
                    "where" => [
                        "Application" => "Vanilla",
                        "Controller" => "Post",
                        "Method" => "Question",
                    ],
                ],
                [
                    "ViewType" => "newIdea",
                    "where" => [
                        "Application" => "Vanilla",
                        "Controller" => "Post",
                        "Method" => "Idea",
                    ],
                ],
                [
                    "ViewType" => "inbox",
                    "where" => [
                        "Application" => "Conversations",
                        "Controller" => "messages",
                        "Method" => "inbox",
                    ],
                ],
            ];
            foreach ($messageDataMigrationMap as $locationMapping) {
                $sql->update("Message")
                    ->set("LayoutViewType", $locationMapping["ViewType"])
                    ->where($locationMapping["where"])
                    ->put();
            }

            $construct->table("Message")->dropColumn("Application");
            $construct->table("Message")->dropColumn("Controller");
            $construct->table("Message")->dropColumn("Method");
        }

        if ($construct->table("Message")->columnExists("CssClass")) {
            $sql->update("Message")
                ->set("Type", "casual")
                ->where("CssClass", "CasualMessage")
                ->put();
            $sql->update("Message")
                ->set("Type", "info")
                ->where("CssClass", "InfoMessage")
                ->put();
            $sql->update("Message")
                ->set("Type", "alert")
                ->where("CssClass", "AlertMessage")
                ->put();
            $sql->update("Message")
                ->set("Type", "warning")
                ->where("CssClass", "WarningMessage")
                ->put();

            $construct->table("Message")->dropColumn("CssClass");
        }
    }
}
