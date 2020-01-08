/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@vanilla/library/src/scripts/styles/styleUtils";
import { cssRaw, cssRule } from "typestyle";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { colorOut } from "@vanilla/library/src/scripts/styles/styleHelpersColors";
import { fullBackgroundCompat } from "@library/layout/Backgrounds";
import { fonts } from "@library/styles/styleHelpersTypography";
import { setAllLinkColors } from "@library/styles/styleHelpersLinks";
import { ButtonTypes, buttonVariables, buttonUtilityClasses, buttonGlobalVariables } from "@library/forms/buttonStyles";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { borders, margins, unit } from "@library/styles/styleHelpers";
import { ColorHelper } from "csx";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { inputVariables, inputClasses } from "@vanilla/library/src/scripts/forms/inputStyles";
import { dropDownClasses } from "@vanilla/library/src/scripts/flyouts/dropDownStyles";
import { siteNavNodeClasses } from "@vanilla/library/src/scripts/navigation/siteNavStyles";

// To use compatibility styles, set '$staticVariables : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export const compatibilityStyles = useThemeCache(() => {
    const vars = globalVariables();
    const formVars = formElementsVariables();
    const mainColors = vars.mainColors;

    // Temporary workaround:
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const metaFg = colorOut(vars.meta.colors.fg);

    fullBackgroundCompat();
    cssRule("body", {
        backgroundColor: bg,
        color: fg,
    });
    cssRule(".Frame", {
        background: "none",
    });

    cssRule(".DataList .Item, .MessageList .Item", {
        background: "none",
    });

    // @mixin font-style-base()
    cssRule("html, body, .DismissMessage", {
        ...fonts({
            family: vars.fonts.families.body,
            color: mainColors.fg,
        }),
    });

    cssRule(".DismissMessage", {
        color: fg,
    });

    cssRule(".InputBox.InputBox.InputBox", inputClasses().inputMixin);
    cssRule(".token-input-list", inputClasses().inputMixin);

    cssRule(".DataTable .Item td", {
        background: bg,
        color: fg,
    });

    // @mixin Button
    mixinButton(".Button-Options", ButtonTypes.ICON_COMPACT);
    mixinButton(".js-poll-result-btn");
    mixinButton(".Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".FormTitleWrapper .Buttons .Button");
    mixinButton(".FormWrapper .Buttons .Button");
    mixinButton(".FormTitleWrapper .Buttons .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".FormWrapper .Buttons .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".Button-Controls .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".BigButton:not(.Danger)", ButtonTypes.PRIMARY);
    mixinButton(".NewConversation.NewConversation", ButtonTypes.PRIMARY);
    mixinButton(".groupToolbar .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".BoxButtons .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".Section-Members .Group-RemoveMember", ButtonTypes.PRIMARY);
    mixinButton(".group-members-filter-box .Button.search", ButtonTypes.PRIMARY);
    mixinButton("#Form_Ban", ButtonTypes.PRIMARY);
    mixinButton(".Popup #UserBadgeForm button", ButtonTypes.PRIMARY);
    mixinButton(".Button.Handle", ButtonTypes.PRIMARY);

    // Standard
    mixinButton(".Button", ButtonTypes.STANDARD);
    mixinButton(".DataList .Item-Col .Options .OptionsLink", ButtonTypes.STANDARD);
    mixinButton(".MessageList .Item-Col .Options .OptionsLink", ButtonTypes.STANDARD);
    mixinButton(".PrevNextPager .Previous", ButtonTypes.STANDARD);
    mixinButton(".PrevNextPager .Next", ButtonTypes.STANDARD);
    mixinButton("div.Popup .Button.change-picture-new", ButtonTypes.STANDARD);
    mixinButton("body.Section-BestOf .FilterMenu a", ButtonTypes.STANDARD);
    mixinButton(".group-members-filter-box .Button", ButtonTypes.STANDARD);
    mixinButton("body.Section-Profile .ProfileOptions .Button-EditProfile", ButtonTypes.STANDARD);
    mixinButton("body.Section-Profile .ProfileOptions .MemberButtons", ButtonTypes.STANDARD);
    mixinButton("body.Section-Profile .ProfileOptions .ProfileButtons-BackToProfile", ButtonTypes.STANDARD);
    mixinButton(".Button.Close", ButtonTypes.STANDARD);

    cssRule(".ReactButton.PopupWindow&:hover .Sprite::before", {
        color: primary,
    });

    cssRule("a.Bookmark &::before", {
        color: primary,
        $nest: {
            "&:hover::before": {
                color: colorOut(mainColors.secondary),
            },
        },
    });
    cssRule(".Box h4", { color: fg });

    // Panel
    cssRule(".Panel a", {
        ...siteNavNodeClasses().linkMixin,
    });

    // Various links
    mixinFontLink(".Navigation-linkContainer a");
    mixinFontLink(".Panel .PanelInThisDiscussion a");
    mixinFontLink(".Panel .Leaderboard a");
    mixinFontLink(".Panel .InThisConversation a");
    mixinFontLink(".FilterMenu a", true);
    mixinFontLink(".Breadcrumbs a", true);
    mixinFontLink("div.Popup .Body a");
    mixinFontLink(".selectBox-toggle");
    mixinFontLink(".followButton");
    mixinFontLink(".QuickSearchButton");
    mixinFontLink(".SelectWrapper::after");
    mixinFontLink(".Back a");
    mixinFontLink(".OptionsLink-Clipboard");
    mixinFontLink("a.OptionsLink");
    mixinFontLink(".MorePager a");
    // Links that have FG color by default but regular state colors.
    mixinFontLink(".ItemContent a", true);
    mixinFontLink(".DataList .Item h3 a", true);
    mixinFontLink(".DataList .Item a.Title", true);
    mixinFontLink(".DataList .Item .Title a", true);
    mixinFontLink("a.Tag", true);
    mixinFontLink(".MenuItems a", true);

    mixinFontLink(".DataTable h2 a", true);
    mixinFontLink(".DataTable h3 a", true);
    mixinFontLink(".DataTable .Title.Title a", true);

    mixinInputBorderColor(`input[type= "text"]`);
    mixinInputBorderColor("textarea");
    mixinInputBorderColor("ul.token-input-list");
    mixinInputBorderColor("input.InputBox");
    mixinInputBorderColor(".InputBox");
    mixinInputBorderColor(".AdvancedSearch select");
    mixinInputBorderColor("select");
    mixinInputBorderColor(".InputBox.BigInput");
    mixinInputBorderColor("ul.token-input-list", "& .token-list-focused");

    // Dropdown hover/focus colors:

    cssRule(".MenuItems, .Flyout.Flyout", {
        background: bg,
        color: fg,
    });
    mixinFlyoutItem(".MenuItems .Item a");
    mixinFlyoutItem(".MenuItems.MenuItems li a");
    mixinFlyoutItem(".Flyout.Flyout li a");
    mixinFlyoutItem(".editor-action.editor-action.editor-action a");

    // Buttons
    cssRule(`.ButtonGroup.Multi .Button.Handle, .ButtonGroup.Multi.Open .Button.Handle`, {
        borderColor: primary,
        borderStyle: vars.border.style,
        borderWidth: unit(vars.border.width),
    });

    cssRule(".Meta-Discussion .Tag", {
        ...margins({
            horizontal: 3,
        }),
    });

    cssRule(".Meta-Discussion > .Tag", {
        marginLeft: unit(6),
    });

    cssRule(
        `
        a.Title,
        .Title a,
        .DataList .Item .Title,
        .DataList .Item.Read .Title,
        .DataList .Item h3,
        .MessageList .Item .Title,
        .MessageList .Item.Read .Title,
        .MessageList .Item h3
        `,
        {
            color: fg,
        },
    );

    cssRule(
        `
        .DataList .Meta .Tag-Announcement,
        .DataList .NewCommentCount,
        .DataList .HasNew.HasNew,
        .MessageList .Tag-Announcement,
        .MessageList .NewCommentCount,
        .MessageList .HasNew.HasNew,
        .DataTableWrap .Tag-Announcement,
        .DataTableWrap .NewCommentCount,
        .DataTableWrap .HasNew.HasNew
        `,
        {
            color: primary,
            borderColor: primary,
            textDecoration: "none",
        },
    );

    cssRule(".Pager > a.Highlight, .Pager > a.Highlight:focus, .Pager > a.Highlight:hover", {
        color: primary,
    });

    cssRule("ul.token-input-list.token-input-focused, .AdvancedSearch .InputBox:focus", {
        borderColor: primary,
    });

    cssRule(
        `
        input[type= "text"],
        textarea,
        ul.token-input-list,
        input.InputBox,
        .AdvancedSearch .InputBox,
        .AdvancedSearch select,
        select,
        ul.token-input-list.token-input-focused,
    `,
        {
            borderRadius: unit(formVars.border.radius),
            color: fg,
            background: bg,
            borderColor: fg,
        },
    );

    cssRule(
        `
        #token-input-Form_tags,
        input[type= "text"],
        textarea,
        ul.token-input-list,
        input.InputBox,
        .InputBox,
        .AdvancedSearch select,
        select,
        .InputBox.BigInput,
        input.SmallInput:focus,
        input.InputBox:focus,
        textarea:focus
        `,
        {
            background: "transparent",
            color: fg,
        },
    );

    cssRule(`div.token-input-dropdown`, borders());

    // Meta colors
    mixinMetaLinkContainer(".DataList .Meta");
    mixinMetaLinkContainer(".MessageList .Meta");
    mixinMetaLinkContainer(".DataList .AuthorInfo");
    mixinMetaLinkContainer(".MessageList .AuthorInfo");
    mixinMetaLinkContainer(".DataList-Search .MItem-Author");
    mixinMetaLinkContainer(".DataList .Excerpt");
    mixinMetaLinkContainer(".DataList .CategoryDescription");
    mixinMetaLinkContainer(".MessageList .Excerpt");
    mixinMetaLinkContainer(".MessageList .CategoryDescription");
    mixinMetaLinkContainer(".Breadcrumbs");
    mixinMetaLinkContainer(".DataList .Tag");
    mixinMetaLinkContainer(".DataList .Tag-Poll");
    mixinMetaLinkContainer(".DataList .RoleTracker");
    mixinMetaLinkContainer(".DataList .IdeationTag");
    mixinMetaLinkContainer(".MessageList .Tag");
    mixinMetaLinkContainer(".MessageList .Tag-Poll");
    mixinMetaLinkContainer(".MessageList .RoleTracker");
    mixinMetaLinkContainer(".MessageList .IdeationTag");
    mixinMetaLinkContainer(".DataTableWrap .Tag");
    mixinMetaLinkContainer(".DataTableWrap .Tag-Poll");
    mixinMetaLinkContainer(".DataTableWrap .RoleTracker");
    mixinMetaLinkContainer(".DataTableWrap .IdeationTag");

    cssRule(
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

    cssRule(
        `
        .MenuItems,
        .Flyout.Flyout,
        .richEditorFlyout,
        `,
        {
            backgroundColor: bg,
            color: fg,
        },
    );

    cssRule(".MenuItems a", {
        color: fg,
    });
});

function mixinFlyoutItem(selector: string) {
    cssRule(selector, dropDownClasses().actionMixin);
}

function mixinMetaLinkContainer(selector: string) {
    const vars = globalVariables();
    const metaFg = colorOut(vars.meta.text.color);

    cssRule(selector, {
        color: metaFg,
        $nest: {
            "& a": {
                color: metaFg,
                fontSize: "inherit",
                textDecoration: "underline",
            },
            "& a:hover": {
                textDecoration: "underline",
            },
            "& a:focus": {
                textDecoration: "underline",
            },
            "& a.focus-visible": {
                textDecoration: "underline",
            },
        },
    });
}

// Mixins replacement
export const mixinFontLink = (selector: string, skipDefaultColor = false) => {
    const linkColors = setAllLinkColors();

    if (!skipDefaultColor) {
        cssRule(selector, {
            color: linkColors.color,
        });
    }

    // $nest doesn't work in this scenario. Working around it by doing it manually.
    // Hopefully a future update will allow us to just pass the nested styles in the cssRule above.
    let rawStyles = `\n`;
    Object.keys(linkColors.nested).forEach(key => {
        const finalSelector = `${selector}${key.replace(/^&+/, "")}`;
        const targetStyles = linkColors.nested[key];
        const keys = Object.keys(targetStyles);
        if (keys.length > 0) {
            rawStyles += `${finalSelector} { `;
            keys.forEach(property => {
                const style = targetStyles[property];
                if (style) {
                    rawStyles += `\n    ${property}: ${style instanceof ColorHelper ? colorOut(style) : style};`;
                }
            });
            rawStyles += `\n}\n\n`;
        }
    });

    cssRaw(rawStyles);
};

export const mixinInputBorderColor = (selector: string, focusSelector?: string) => {
    const vars = globalVariables();
    const primary = colorOut(vars.mainColors.primary);
    let extraFocus = {};
    if (focusSelector) {
        extraFocus = {
            [focusSelector]: {
                borderColor: primary,
            },
        };
    }

    cssRule(selector, {
        borderColor: colorOut(vars.border.color),
        borderStyle: vars.border.style,
        borderWidth: unit(vars.border.width),
        $nest: {
            "&:focus": {
                borderColor: primary,
            },
            "& .focus-visible": {
                borderColor: primary,
            },
            ...extraFocus,
        },
    });
};

export const mixinButton = (selector: string, buttonType: ButtonTypes = ButtonTypes.STANDARD) => {
    const vars = buttonVariables();

    if (buttonType === ButtonTypes.PRIMARY) {
        cssRule(selector, generateButtonStyleProperties(vars.primary));
    } else if (buttonType === ButtonTypes.STANDARD) {
        cssRule(selector, generateButtonStyleProperties(vars.standard));
    } else if (buttonType === ButtonTypes.ICON_COMPACT) {
        cssRule(selector, buttonUtilityClasses().iconMixin(buttonGlobalVariables().sizing.compactHeight));
    } else {
        new Error(`No support yet for button type: ${buttonType}`);
    }
};

export const mixinCloseButton = (selector: string) => {
    const vars = globalVariables();
    cssRule(selector, {
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
