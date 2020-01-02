/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@vanilla/library/src/scripts/styles/styleUtils";
import { cssRule } from "typestyle";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { colorOut } from "@vanilla/library/src/scripts/styles/styleHelpersColors";
import { fullBackgroundCompat } from "@library/layout/Backgrounds";
import { fonts } from "@library/styles/styleHelpersTypography";
import { setAllLinkColors } from "@library/styles/styleHelpersLinks";
import { ButtonTypes, buttonVariables } from "@library/forms/buttonStyles";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { NestedCSSProperties } from "typestyle/lib/types";

// To use compatibility styles, set '$colorFromDynamicTheme : true;' in custom.scss
// $Configuration['Feature']['DeferredLegacyScripts']['Enabled'] = true;
export const compatibilityStyles = useThemeCache(() => {
    const vars = globalVariables();
    const mainColors = vars.mainColors;
    const fg = colorOut(mainColors.fg);
    const bg = colorOut(mainColors.bg);
    const primary = colorOut(mainColors.primary);
    const secondary = colorOut(mainColors.secondary);
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

    // @mixin Button
    mixinButton(".js-poll-result-btn");
    mixinButton(".Button.Primary", ButtonTypes.PRIMARY);
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

    cssRule(".Breadcrumbs", {
        color: colorOut(vars.meta.colors.fg),
    });

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

    mixinFontLink(".Navigation-linkContainer a");

    cssRule(".Box h4", { color: fg });

    mixinFontLink(".Panel .PanelInThisDiscussion a");
    mixinFontLink(".Panel .Leaderboard a");
    mixinFontLink(".Panel .InThisConversation a");
    mixinFontLink(".FilterMenu a");
    mixinFontLink("div.Popup .Body a");

    cssRule(".ButtonGroup.Multi .Button.Handle, .ButtonGroup.Multi.Open .Button.Handle", {
        borderColor: primary,
    });
});

// Mixins replacement
export const mixinFontLink = (selector: string) => {
    const linkColors = setAllLinkColors();
    cssRule(selector, {
        color: linkColors.color,
        $nest: linkColors.nested,
    });
};

export const mixinButton = (selector: string, buttonType: ButtonTypes = ButtonTypes.STANDARD) => {
    const vars = buttonVariables();
    if (buttonType === ButtonTypes.PRIMARY) {
        cssRule(selector, generateButtonStyleProperties(vars.primary));
    } else if (buttonType === ButtonTypes.STANDARD) {
        cssRule(selector, generateButtonStyleProperties(vars.standard));
    } else {
        new Error(`No support yet for button type: ${buttonType}`);
    }
};
