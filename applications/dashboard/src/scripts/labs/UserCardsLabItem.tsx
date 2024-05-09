/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import image from "!file-loader!./UserCardsLabItem.svg";
import { LabThemeEditorNote } from "@dashboard/labs/LabThemeEditorNote";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";

export function UserCardsLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            themeFeatureName="UserCards"
            labName={"userCards"}
            name={t("Usercards")}
            description={t(
                "Usercards allow you to get a quick in-line snapshot of a user's information.",
                "Usercards allow you to get a quick in-line snapshot of a user's information. When viewing posts and leaderboards, click on the username to see a card showcasing the users basic profile info without having to navigate away from the page. Enable this feature to add usercards to your custom theme.",
            )}
            notes={
                <LabThemeEditorNote
                    translatedLabName={t("Usercards")}
                    docsUrl="https://success.vanillaforums.com/kb/articles/322-user-cards"
                />
            }
        />
    );
}
