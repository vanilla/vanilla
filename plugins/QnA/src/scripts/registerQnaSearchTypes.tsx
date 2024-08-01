/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { TypeQuestionIcon } from "@library/icons/searchIcons";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import DISCUSSIONS_SEARCH_DOMAIN from "@vanilla/addon-vanilla/search/DiscussionsSearchDomain";
import { t } from "@vanilla/i18n";
import React from "react";

export function registerQnaSearchTypes() {
    DISCUSSIONS_SEARCH_DOMAIN.addSubType({
        label: t("Question"),
        icon: <TypeQuestionIcon />,
        type: "question",
    });

    DISCUSSIONS_SEARCH_DOMAIN.addSubType({
        label: t("Answer"),
        icon: <TypeQuestionIcon />,
        type: "answer",
    });

    CommunityPostTypeFilter.addPostType({
        label: t("Questions"),
        values: ["question", "answer"],
    });
}
