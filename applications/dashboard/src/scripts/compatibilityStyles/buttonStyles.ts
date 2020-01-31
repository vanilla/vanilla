/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssOut, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { buttonGlobalVariables, ButtonTypes, buttonUtilityClasses, buttonVariables } from "@library/forms/buttonStyles";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";

export const buttonCSS = () => {
    const globalVars = globalVariables();
    const mainColors = globalVars.mainColors;
    const primary = colorOut(mainColors.primary);

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
    mixinButton("div.Popup .Body .Button.Primary", ButtonTypes.PRIMARY);
    cssOut(`.Button.Primary:not([disabled]):hover`, {
        color: colorOut(globalVars.mainColors.primaryContrast),
    });

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

    cssOut(".Panel-main .ApplyButton", {
        width: "auto",
    });

    cssOut(`.ButtonGroup.Multi .Button.Handle, .ButtonGroup.Multi.Open .Button.Handle`, {
        borderColor: primary,
        borderStyle: globalVars.border.style,
        borderWidth: unit(globalVars.border.width),
        color: colorOut(globalVars.mainColors.primaryContrast),
        backgroundColor: colorOut(globalVars.mainColors.primary),
    });
};

export const mixinButton = (selector: string, buttonType: ButtonTypes = ButtonTypes.STANDARD) => {
    const vars = buttonVariables();
    selector = trimTrailingCommas(selector);

    if (buttonType === ButtonTypes.PRIMARY) {
        cssOut(selector, generateButtonStyleProperties(vars.primary));
    } else if (buttonType === ButtonTypes.STANDARD) {
        cssOut(selector, generateButtonStyleProperties(vars.standard));
    } else if (buttonType === ButtonTypes.ICON_COMPACT) {
        cssOut(selector, buttonUtilityClasses().iconMixin(buttonGlobalVariables().sizing.compactHeight));
    } else {
        new Error(`No support yet for button type: ${buttonType}`);
    }
};
