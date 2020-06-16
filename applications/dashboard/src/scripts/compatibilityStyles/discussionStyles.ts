/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    importantColorOut,
    unit,
    colorOut,
    backgroundHelper,
    ColorValues,
    absolutePosition,
    negativeUnit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { important, percent, quote, translate } from "csx";
import { iconClasses } from "@library/icons/iconStyles";

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
        ".Meta.Meta-Discussion",

        {
            display: vars.meta.display,
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
            width: unit(24),
            height: unit(24),
            display: "block",
            position: "relative",
        },
    );

    cssOut(
        `
        .Content a.Bookmark,
        .Content a.Bookmarking,
        .Content a.Bookmarked`,
        {
            cursor: "pointer",
        },
    );

    cssOut(
        `
        .Content a.Bookmark .svgBookmark,
        .Content a.Bookmarking .svgBookmark,
        .Content a.Bookmarked .svgBookmark`,
        {
            ...absolutePosition.topLeft("50%", "50%"),
            content: quote(``),
            display: "block",
            width: unit(12),
            height: unit(16),
            fontSize: unit(12),
            transform: translate(`-50%`, `-50%`),
        },
    );

    cssOut(
        `
        body.Discussions .DataList .Options,
        body.Discussions .MessageList .Options
    `,
        {
            marginTop: negativeUnit(2),
        },
    );
};
