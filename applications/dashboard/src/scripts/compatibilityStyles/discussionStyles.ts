/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { importantColorOut, unit, colorOut, backgroundHelper } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { bookmarkBackground } from "@dashboard/compatibilityStyles/svgsAsBackgrounds";
import { important, percent, quote } from "csx";

export const discussionCSS = () => {
    const vars = globalVariables();

    cssOut(
        `
        .userContent,
        .UserContent,
        .MessageList.Discussion
        `,
        {
            color: colorOut(vars.mainColors.fg),
            fontSize: unit(vars.fonts.size.medium),
        },
    );

    cssOut(
        `
        .userContent.userContent h1,
        .userContent.userContent h2,
        .userContent.userContent h3,
        .userContent.userContent h4,
        .userContent.userContent h5,
        .userContent.userContent h6
    `,
        {
            color: colorOut(vars.mainColors.fg),
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
            color: colorOut(vars.mainColors.primaryContrast),
            backgroundColor: importantColorOut(vars.mixPrimaryAndBg(vars.constants.stateColorEmphasis)),
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
            top: 2,
            left: 2,
        },
    );

    cssOut(`.Container .DataTable span.MItem`, {
        display: "inline-block",
    });

    cssOut(`.Container .DataTable span.MItem a`, {
        display: "inline",
    });

    cssOut(`.MessageList .ItemDiscussion .InlineTags`, {
        margin: 0,
        padding: 0,
    });

    cssOut(
        `
        .Options a.Bookmark,
        .Options a.Bookmarking,
        .Options a.Bookmarked
        `,
        {
            opacity: 1,
            width: unit(14),
            height: unit(20),
            display: "block",
        },
    );

    cssOut(
        `
        .Content a.Bookmark::before,
        .Content a.Bookmarking::before,
        .Content a.Bookmarked::before`,
        {
            content: quote(``),
            display: "block",
            width: unit(12),
            height: unit(16),
            fontSize: unit(12),
        },
    );

    cssOut(
        `
        .Content a.Bookmark::before,
        .Content a.Bookmarking::before
        `,
        {
            ...backgroundHelper({ size: "100%", image: bookmarkBackground(false, vars.mixPrimaryAndBg(0.8)) }),
        },
    );

    cssOut(`.Content a.Bookmarking`, {
        cursor: important("wait"),
        background: "none",
        opacity: ".5",
    });

    cssOut(
        `
        .Content a.Bookmarked::before
        `,
        {
            ...backgroundHelper({ size: "100%", image: bookmarkBackground(true, vars.mainColors.primary) }),
        },
    );
};
