/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import image from "!file-loader!./NewPostMenuLabItem.svg";
import { LabThemeEditorNote } from "@dashboard/labs/LabThemeEditorNote";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";

export function NewPostMenuLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            themeFeatureName="NewPostMenu"
            labName={"newPostMenu"}
            name={t("New Post Menu")}
            description={t(
                "With new Post Menu you'll have a new look for our NewDiscussion module.",
                "With new Post Menu you'll have a new look for our NewDiscussion module. This feature also enables new Post Menu floating button on smaller views.",
            )}
            notes={
                <LabThemeEditorNote
                    translatedLabName={t("New Post Menu")}
                    docsUrl="https://success.vanillaforums.com/kb/articles/416-new-post-menu"
                />
            }
        />
    );
}
