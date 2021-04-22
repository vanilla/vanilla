/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import image from "!file-loader!./NewSearchPageLabItem.svg";
import { LabThemeEditorNote } from "@dashboard/labs/LabThemeEditorNote";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";

export function NewSearchPageLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            themeFeatureName="useNewSearchPage"
            labName={"newSearchPage"}
            name={t("New Search Page")}
            description={t(
                "Vanilla's new search service is finally here.",
                "Vanilla's new search service is finally here. Enable our new search page UI to gain access to the newest search features such as Member Search, search sorting and term highlighting.",
            )}
            notes={
                <LabThemeEditorNote
                    translatedLabName={t("New Search Page")}
                    docsUrl="https://success.vanillaforums.com/kb/articles/383-enable-and-configure-vanillas-new-search-ui"
                />
            }
        />
    );
}
