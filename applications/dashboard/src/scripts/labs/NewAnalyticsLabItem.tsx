/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import image from "!file-loader!./NewAnalyticsLabItem.svg";
import { LabThemeEditorNote } from "@dashboard/labs/LabThemeEditorNote";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";

export function NewAnalyticsLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            themeFeatureName="NewAnalytics"
            labName={"newAnalytics"}
            name={t("Analytics BETA")}
            description={t(
                "Our new Analytics Experience is finally here, and we want your feedback.",
                "Our new Analytics Experience is finally here, and we want your feedback. Enable this option to test out our new Analytics UI, Custom Dashboard Builder and Chart Editor and access more of your analytics data.",
            )}
            notes={
                <LabThemeEditorNote
                    translatedLabName={t("Analytics BETA")}
                    docsUrl="https://success.vanillaforums.com/kb/articles/433-analytics-beta"
                    customShortSourceText="Check out our <0/>"
                    customSourceText="Check out our <1>Analytics docs</1> for more info."
                />
            }
            reloadPageAfterToggle
        />
    );
}
