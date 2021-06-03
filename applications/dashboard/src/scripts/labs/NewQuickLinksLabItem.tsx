/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import image from "!file-loader!./NewQuickLinksLabItem.svg";
import { LabThemeEditorNote } from "@dashboard/labs/LabThemeEditorNote";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";

export function NewQuickLinksLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            themeFeatureName="NewQuickLinks"
            labName={"newQuickLinks"}
            name={t("New Quick Links")}
            description={t(
                "Quick Links is a default component on community pages.",
                "Quick Links is a default component on community pages. Enable our new Quick Links component to add, edit or hide links from this menu using our theme editor.",
            )}
            notes={
                <LabThemeEditorNote
                    translatedLabName={t("New Quick Links")}
                    docsUrl="https://success.vanillaforums.com/kb/articles/365-customizing-the-quick-links-menu"
                />
            }
        />
    );
}
