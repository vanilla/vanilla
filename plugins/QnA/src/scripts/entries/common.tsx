/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { onReady, t } from "@vanilla/library/src/scripts/utility/appUtils";
import { SearchFormContextProvider } from "@vanilla/library/src/scripts/search/SearchFormContext";
import { TypeQuestionIcon } from "@vanilla/library/src/scripts/icons/searchIcons";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";

onReady(() => {
    SearchFormContextProvider.addSubType({
        label: t("Question"),
        icon: <TypeQuestionIcon />,
        recordType: "discussion",
        type: "question",
    });

    SearchFormContextProvider.addSubType({
        label: t("Answer"),
        icon: <TypeQuestionIcon />,
        recordType: "comment",
        type: "answer",
    });

    CommunityPostTypeFilter.addPostType({
        label: t("Questions"),
        values: ["question", "answer"],
    });
});
