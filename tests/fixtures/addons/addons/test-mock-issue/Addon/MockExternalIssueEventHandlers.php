<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\addons\addons\TestMockIssue\Addon;

use AttachmentModel;
use Garden\EventHandlersInterface;

/**
 * Event handlers for mock issue provider.
 */
class MockExternalIssueEventHandlers implements EventHandlersInterface
{
    /**
     * Normalize attachments.
     *
     * @param $Sender
     * @param $Args
     * @return void
     */
    public function attachmentModel_normalizeAttachments_handler($Sender, $Args): void
    {
        $attachments = &$Args["Attachments"];
        foreach ($attachments as &$attachment) {
            if ($attachment["Type"] == "mock-issue") {
                $attachment = array_merge($attachment, AttachmentModel::splitForeignID($attachment["ForeignID"]));
                AttachmentModel::addSpecialFields($attachment, ["SpecialMockField1", "SpecialMockField2"]);
            }
        }
    }
}
