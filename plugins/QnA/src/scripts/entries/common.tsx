/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { onReady, t } from "@vanilla/library/src/scripts/utility/appUtils";
import { SearchService } from "@library/search/SearchService";
import { TypeQuestionIcon } from "@vanilla/library/src/scripts/icons/searchIcons";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";

onReady(() => {
    SearchService.addSubType({
        label: t("Question"),
        icon: <TypeQuestionIcon />,
        recordType: "discussion",
        type: "question",
    });

    SearchService.addSubType({
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
