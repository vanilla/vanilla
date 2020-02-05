/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@vanilla/library/src/scripts/styles/styleUtils";
import { cssRaw, cssRule, media } from "typestyle";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { colorOut } from "@vanilla/library/src/scripts/styles/styleHelpersColors";
import { fullBackgroundCompat } from "@library/layout/Backgrounds";
import { fonts } from "@library/styles/styleHelpersTypography";
import {
    borders,
    importantUnit,
    margins,
    negative,
    paddings,
    singleBorder,
    srOnly,
    unit,
} from "@library/styles/styleHelpers";
import { calc, ColorHelper, important } from "csx";
import { inputVariables } from "@vanilla/library/src/scripts/forms/inputStyles";
import { siteNavNodeClasses } from "@vanilla/library/src/scripts/navigation/siteNavStyles";
import { socialConnectCSS } from "@dashboard/compatibilityStyles/socialConnectStyles";
import { reactionsCSS } from "@dashboard/compatibilityStyles/reactionsStyles";
import * as types from "typestyle/lib/types";
import { buttonCSS } from "@dashboard/compatibilityStyles/buttonStyles";
import { inputCSS } from "@dashboard/compatibilityStyles/inputStyles";
import { flyoutCSS } from "@dashboard/compatibilityStyles/flyoutStyles";
import { textLinkCSS } from "@dashboard/compatibilityStyles/textLinkStyles";
import { forumMetaCSS } from "@dashboard/compatibilityStyles/forumMetaStyles";
import { paginationCSS } from "@dashboard/compatibilityStyles/paginationStyles";
import { fontCSS } from "./fontStyles";
import { forumLayoutCSS, forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { categoriesCSS } from "@dashboard/compatibilityStyles/categoriesStyles";
import { bestOfCSS } from "@dashboard/compatibilityStyles/bestOfStyles";
import { ideaCSS } from "@dashboard/compatibilityStyles/ideaStyles";
import { tableCSS } from "@dashboard/compatibilityStyles/tableStyles";
import { discussionCSS } from "./discussionStyles";
import { searchPageCSS } from "./searchPageStyles";
import { groupsCSS } from "@dashboard/compatibilityStyles/groupsStyles";
import { profilePageCSS } from "@dashboard/compatibilityStyles/profilePageSyles";
import { photoGridCSS } from "@dashboard/compatibilityStyles/photoGridStyles";
import { messagesCSS } from "@dashboard/compatibilityStyles/messagesStyles";

// To use compatibility styles, set '$staticVariables : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export const compatibilityStyles = useThemeCache(() => {
    const vars = globalVariables();
    const layoutVars = forumLayoutVariables();
    const mainColors = vars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);

    fullBackgroundCompat();

    cssOut("body", {
        backgroundColor: bg,
        color: fg,
    });

    cssOut(".Frame", {
        background: "none",
        overflow: "auto",
    });

    cssOut(`.Content .DataList .Item, .Content .MessageList .Item`, {
        background: "none",
        borderColor: colorOut(vars.border.color),
        ...paddings(layoutVars.cell.paddings),
    });

    // @mixin font-style-base()
    cssOut("html, body, .DismissMessage", {
        ...fonts({
            family: vars.fonts.families.body,
            color: mainColors.fg,
        }),
    });

    cssOut(
        ".DismissMessage, .DataList .Excerpt, .DataList .CategoryDescription, .MessageList .Excerpt, .MessageList .CategoryDescription",
        {
            color: fg,
        },
    );

    cssOut(`.DataTable .Item td, .Item .Poll .PollOption`, {
        background: bg,
        color: fg,
    });

    cssOut(".ReactButton.PopupWindow&:hover .Sprite::before", {
        color: primary,
    });

    cssOut(`a.Bookmark`, {
        opacity: 1,
        $nest: {
            "&::before": {
                color: primary,
            },
            "&:hover::before": {
                color: colorOut(mainColors.secondary),
            },
        },
    });

    cssOut(
        `
        .Content a.Bookmarked::before,
        .Content a.Bookmarked:hover::before
        `,
        {
            color: important(colorOut(mainColors.primary) as string),
        },
    );

    cssOut(".Box h4", { color: fg });

    cssOut(`.CategoryBox > .OptionsMenu`, {
        marginRight: unit(layoutVars.cell.paddings.horizontal),
    });

    // Panel
    cssOut(".Panel a, .About a", {
        ...siteNavNodeClasses().linkMixin,
    });

    cssOut(
        `
        a.Title,
        .Title a,
        .DataList .Item .Title,
        .DataList .Item.Read .Title,
        .DataList .Item h3,
        .MessageList .Item .Title,
        .MessageList .Item.Read .Title,
        .MessageList .Item h3
        .MenuItems a
        `,
        {
            color: fg,
        },
    );

    cssOut(".Pager > a.Highlight, .Pager > a.Highlight:focus, .Pager > a.Highlight:hover", {
        color: primary,
    });

    cssOut(
        `
        .Herobanner .SearchBox .AdvancedSearch .BigInput,
        .Herobanner .SearchBox #Form_Search
    `,
        {
            borderRight: 0,
            backgroundColor: bg,
            color: fg,
            ...borders(inputVariables().border),
            borderTopRightRadius: 0,
            borderBottomRightRadius: 0,
        },
    );

    cssOut(`div.Popup .Body`, {
        // borderRadius: unit(vars.border.radius),
        ...borders(),
        backgroundColor: bg,
        color: fg,
    });

    cssOut(`.DataList .Item ~ .CategoryHeading::before, .MessageList .Item ~ .CategoryHeading::before`, {
        borderBottom: singleBorder(),
    });

    cssOut(`div.Popup p`, {
        paddingLeft: 0,
        paddingRight: 0,
    });

    cssOut(
        `
        .MessageList .ItemComment .MItem.RoleTracker a,
        .MessageList .ItemDiscussion .MItem.RoleTracker a
        `,
        {
            textDecoration: "none",
        },
    );

    cssOut(`.Herobanner-bgImage`, {
        "-webkit-filter": "none",
        filter: "none",
    });

    cssOut(".Herobanner .SearchBox .AdvancedSearch .BigInput", {
        borderTopRightRadius: important(0),
        borderBottomRightRadius: important(0),
    });

    cssOut(
        `
        a:hover,
        a.TextColor:hover,
        a:hover .TextColor`,
        {
            color: colorOut(vars.links.colors.hover),
        },
    );

    cssOut(
        `
        a.TextColor, a .TextColor`,

        {
            color: colorOut(vars.mainColors.fg),
        },
    );

    cssOut(".ButtonGroup .Dropdown", {
        marginTop: unit(negative(vars.border.width)),
    });

    cssOut(`.QuickSearchButton`, {
        color: fg,
        ...borders(),
    });

    cssOut(`.DataList.CategoryList .Item[class*=Depth]`, {
        ...paddings({
            vertical: vars.gutter.size,
            horizontal: importantUnit(vars.gutter.half),
        }),
    });

    cssOut(".MenuItems, .Flyout.Flyout", {
        ...borders(),
    });

    cssOut(`.Frame-content`, {
        marginTop: unit(vars.gutter.size * 2),
    });

    cssOut(`.PageControls.PageControls .selectBox`, {
        height: "auto",
    });

    cssOut(`.Content .PageControls`, {
        marginBottom: unit(24),
        ...paddings({
            horizontal: layoutVars.cell.paddings.horizontal,
        }),
    });

    cssOut(`.DataList .Item:last-child, .MessageList .Item:last-child`, {
        borderTopColor: colorOut(vars.border.color),
    });

    cssOut(`.Author a:not(.PhotoWrap)`, {
        fontWeight: vars.fonts.weights.bold,
    });

    cssOut(
        `
        .Tag,
        .DataList .Meta .Tag-Announcement,
        .DataList .NewCommentCount,
        .DataList .HasNew.HasNew,
        .MessageList .Tag-Announcement,
        .MessageList .NewCommentCount,
        .MessageList .HasNew.HasNew,
        .DataTableWrap .Tag-Announcement,
        .DataTableWrap .NewCommentCount,
        .DataTableWrap .HasNew.HasNew,
        .MessageList .ItemComment .Username,
        .MessageList .ItemDiscussion .Username,
        .Content .DataList .Tag,
        .Content .DataList .Tag-Poll,
        .Content .DataList .RoleTracker,
        .Content .DataList .IdeationTag,
        .Content .MessageList .Tag,
        .Content .MessageList .Tag-Poll,
        .Content .MessageList .RoleTracker,
        .Content .MessageList .IdeationTag,
        .Content .DataTableWrap .Tag,
        .Content .DataTableWrap .Tag-Poll,
        .Content .DataTableWrap .RoleTracker,
        .Content .DataTableWrap .IdeationTag
        `,
        {
            ...borders(),
            background: "none",
            backgroundColor: "transparent",
            ...fonts({
                color: vars.meta.text.color,
                size: vars.meta.text.fontSize,
            }),
            textDecoration: "none",
        },
    );

    cssOut(`a.Tag`, {
        $nest: {
            "&:hover, &:focus, &:active": {
                textDecoration: important("none"),
                borderColor: colorOut(vars.mainColors.fg),
            },
        },
    });

    cssOut(`.DataList.Discussions .Item .Title a`, {
        textDecoration: important("none"),
    });

    cssOut(`a.Bookmark`, {
        ...srOnly(),
    });

    cssOut(
        `
        .Container a.UserLink,
        .Container a.UserLink.BlockTitle
    `,
        {
            fontWeight: vars.fonts.weights.bold,
        },
    );

    cssOut(".Panel > * + *", {
        marginTop: unit(vars.gutter.size * 2),
    });

    cssOut(`#ConversationForm label`, {
        color: colorOut(vars.mainColors.fg),
    });

    cssOut(`.Group-Box.Group-MembersPreview .PageControls .H`, {
        position: "relative",
    });

    buttonCSS();
    flyoutCSS();
    textLinkCSS();
    forumMetaCSS();
    inputCSS();
    socialConnectCSS();
    reactionsCSS();
    paginationCSS();
    forumLayoutCSS();
    fontCSS();
    categoriesCSS();
    bestOfCSS();
    ideaCSS();
    tableCSS();
    discussionCSS();
    searchPageCSS();
    groupsCSS();
    profilePageCSS();
    photoGridCSS();
    messagesCSS();
});

export const mixinCloseButton = (selector: string) => {
    const vars = globalVariables();
    cssOut(selector, {
        color: colorOut(vars.mainColors.fg),
        background: "none",
        $nest: {
            "&:hover": {
                color: colorOut(vars.mainColors.primary),
            },
            "&:focus": {
                color: colorOut(vars.mainColors.primary),
            },
            "&.focus-visible": {
                color: colorOut(vars.mainColors.primary),
            },
        },
    });
};

export const trimTrailingCommas = selector => {
    return selector.trim().replace(new RegExp("[,]+$"), "");
};

export const cssOut = (selector: string, ...objects: types.NestedCSSProperties[]) => {
    cssRule(trimTrailingCommas(selector), ...objects);
};

export const camelCaseToDash = (str: string) => {
    return str.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
};

export const nestedWorkaround = (selector: string, nestedObject: {}) => {
    // $nest doesn't work in this scenario. Working around it by doing it manually.
    // Hopefully a future update will allow us to just pass the nested styles in the cssOut above.

    let rawStyles = `\n`;
    Object.keys(nestedObject).forEach(key => {
        const finalSelector = `${selector}${key.replace(/^&+/, "")}`;
        const targetStyles = nestedObject[key];
        const keys = Object.keys(targetStyles);
        if (keys.length > 0) {
            rawStyles += `${finalSelector} { `;
            keys.forEach(property => {
                const style = targetStyles[property];
                if (style) {
                    rawStyles += `\n    ${camelCaseToDash(property)}: ${
                        style instanceof ColorHelper ? colorOut(style) : style
                    };`;
                }
            });
            rawStyles += `\n}\n\n`;
        }
    });

    cssRaw(rawStyles);
};
