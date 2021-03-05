/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { cssOut } from "@dashboard/compatibilityStyles";
import { Mixins } from "@library/styles/Mixins";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";

export const discussionListCompatCSS = () => {
    const globalVars = globalVariables();
    const vars = discussionListVariables();

    MixinsFoundation.contentBoxes(vars.contentBoxes, "DiscussionList");
    MixinsFoundation.contentBoxes(vars.panelBoxes, "DiscussionList", ".Panel");

    cssOut("li.ItemDiscussion.ItemDiscussion", {
        display: "flex",
        flexWrap: "wrap",

        "& .ItemContent": {
            flex: 1,
        },

        "& .Title": {
            marginTop: -4,
            display: "inline-block",
        },

        "& .Excerpt": {
            marginTop: 0,
        },
    });

    cssOut(".ItemDiscussion.ItemDiscussion", {
        "& > .PhotoWrap, & > .userCardWrapper-photo, & > .idea-counter-module": {
            marginRight: 16,
        },

        "& .idea-counter-box": {
            marginRight: 0,
        },

        "& .AdminCheck": {
            height: "100%",
            marginTop: 6,
            marginRight: 6,
        },

        "& .Title a": {
            ...Mixins.font(vars.item.title.font),
        },

        "&.Read .Title a": {
            ...Mixins.font(vars.item.title.fontRead),
        },

        "& .Title a:hover, & .Title a:active, & .Title a:focus": {
            ...Mixins.font(vars.item.title.fontState),
        },
    });
};
