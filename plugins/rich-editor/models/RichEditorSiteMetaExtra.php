<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Models\SiteMetaExtra;

/**
 * Provide meta so that FE can load the correct version of the editor
 */
class RichEditorSiteMetaExtra extends SiteMetaExtra
{
    public const INPUT_FORMATTER = "Garden.InputFormatter";
    public const MOBILE_INPUT_FORMATTER = "Garden.MobileInputFormatter";

    /**
     * @inheritDoc
     */
    public function getValue(): array
    {
        return [
            "inputFormat" => [
                "desktop" => (string) Gdn::config(self::INPUT_FORMATTER),
                "mobile" => (string) Gdn::config(self::MOBILE_INPUT_FORMATTER),
            ],
        ];
    }
}
