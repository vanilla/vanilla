/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { importantColorOut, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export const discussionCSS = () => {
    const globalVars = globalVariables();

    cssOut(
        `
        .userContent
        `,
        {
            fontSize: unit(globalVars.fonts.size.medium),
        },
    );

    // Polls

    cssOut(
        `
        .Item .Poll .PollOptions .PollColor,
        .Item .Poll .PollOptions .PollColor.PollColor1,
        .Item .Poll .PollOptions .PollColor.PollColor2,
        .Item .Poll .PollOptions .PollColor.PollColor3,
        .Item .Poll .PollOptions .PollColor.PollColor4,
        .Item .Poll .PollOptions .PollColor.PollColor5,
        .Item .Poll .PollOptions .PollColor.PollColor6,
        .Item .Poll .PollOptions .PollColor.PollColor7,
        .Item .Poll .PollOptions .PollColor.PollColor8,
        .Item .Poll .PollOptions .PollColor.PollColor9,
        .Item .Poll .PollOptions .PollColor.PollColor10,
    `,
        {
            backgroundColor: importantColorOut(
                globalVars.mixPrimaryAndBg(globalVars.getRatioBasedOnBackgroundDarkness(0.85)),
            ),
            opacity: 1,
        },
    );

    cssOut(
        `
        .DiscussionHeader .AuthorWrap,
        .MessageList .ItemComment .AuthorWrap,
        .MessageList .ItemDiscussion .AuthorWrap,
        `,
        {
            position: "relative",
        },
    );

    cssOut(
        `
        .MessageList .ItemDiscussion .Item-Header.DiscussionHeader .PhotoWrap,
        .MessageList .ItemComment .Item-Header .PhotoWrap,
        .MessageList .ItemDiscussion .Item-Header .PhotoWrap
        `,
        {
            top: unit(4),
        },
    );
};
