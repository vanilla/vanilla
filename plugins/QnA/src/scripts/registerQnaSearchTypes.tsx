/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { TypeQuestionIcon } from "@library/icons/searchIcons";
import { SearchService } from "@library/search/SearchService";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import { t } from "@vanilla/i18n";
import React from "react";

export function registerQnaSearchTypes() {
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
}
