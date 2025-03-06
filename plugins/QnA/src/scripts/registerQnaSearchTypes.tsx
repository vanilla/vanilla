/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Icon } from "@vanilla/icons";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import DISCUSSIONS_SEARCH_DOMAIN from "@vanilla/addon-vanilla/search/DiscussionsSearchDomain";
import { t } from "@vanilla/i18n";

export function registerQnaSearchTypes() {
    DISCUSSIONS_SEARCH_DOMAIN.addSubType({
        label: t("Question"),
        icon: <Icon icon={"search-questions"} />,
        type: "question",
    });

    DISCUSSIONS_SEARCH_DOMAIN.addSubType({
        label: t("Answer"),
        icon: <Icon icon={"search-questions"} />,
        type: "answer",
    });

    CommunityPostTypeFilter.addPostType({
        label: t("Questions"),
        values: ["question", "answer"],
    });
}
