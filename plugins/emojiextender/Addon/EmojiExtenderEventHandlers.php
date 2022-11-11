<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\EmojiExtender\Addon;

use ConfigurationModule;
use Emoji;
use Garden\EventHandlersInterface;
use Gdn_Format;
use SettingsController;
use Vanilla\EmojiExtender\EmojiExtenderModel;

class EmojiExtenderEventHandlers implements EventHandlersInterface
{
    /** @var EmojiExtenderModel */
    private $emojiExtender;

    public function __construct(EmojiExtenderModel $emojiExtender)
    {
        $this->emojiExtender = $emojiExtender;
    }

    /**
     * Configure settings page in dashboard.
     *
     * @param SettingsController $sender
     */
    public function settingsController_emojiExtender_create($sender)
    {
        $sender->permission("Garden.Settings.Manage");

        $items = [];
        foreach ($this->emojiExtender->getEmojiSets() as $key => $emojiSet) {
            $manifest = $this->emojiExtender->getManifest($emojiSet);
            $data = [
                "iconUrl" => isset($manifest["icon"])
                    ? asset($emojiSet["basePath"] . "/" . $manifest["icon"], true, true)
                    : "",
                "name" => $manifest["name"],
                "author" => !empty($manifest["author"]) ? sprintf(t("by %s"), $manifest["author"]) : null,
                "descriptionHtml" => Gdn_Format::text($manifest["description"]),
            ];

            $items[$key] = $this->emojiExtender->renderTwig("/plugins/emojiextender/views/settings.twig", $data);
        }
        $cf = new ConfigurationModule($sender);
        $cf->initialize([
            "Garden.EmojiSet" => [
                "LabelCode" => "Emoji Set",
                "Control" => "radiolist",
                "Items" => $items,
                "Options" => [
                    "list" => true,
                    "list-item-class" => "label-selector-item",
                    "listclass" => "emojiext-list label-selector",
                    "display" => "after",
                    "class" => "label-selector-input",
                    "no-grid" => true,
                ],
            ],
        ]);

        $sender->setData("Title", t("Choose Your Emoji Set"));
        $cf->renderAll();
    }
}
