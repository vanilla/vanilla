/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { discussionListVariables } from "@dashboard/compatibilityStyles/pages/DiscussionList.variables";
import { cssOut } from "@dashboard/compatibilityStyles";
import { Mixins } from "@library/styles/Mixins";

export const discussionListCompatCSS = () => {
    const globalVars = globalVariables();
    const vars = discussionListVariables();

    MixinsFoundation.contentBoxes(vars.contentBoxes, "DiscussionList");

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
        "& > .ProfilePhoto, & > .userCardWrapper-photo, & > .idea-counter-module": {
            marginRight: 16,
        },

        "& .AdminCheck": {
            height: "100%",
            marginTop: 6,
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
