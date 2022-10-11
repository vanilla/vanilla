/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import image from "!file-loader!./NewEmbedSystemLabItem.svg";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";

export function NewEmbedSystemLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            labName={"newEmbedSystem"}
            name={t("New Embed System")}
            description={t(
                "The new embed system has improved performance.",
                "The new embed system has improved performance, improved scrolling behaviour, and more consistent behaviour than the old one. Currently comment and wordpress embeds are unsupported in the New Embed System.",
            )}
        />
    );
}
