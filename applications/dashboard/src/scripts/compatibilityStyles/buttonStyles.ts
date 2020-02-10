/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssOut, trimTrailingCommas } from "@dashboard/compatibilityStyles/index";
import { buttonGlobalVariables, ButtonTypes, buttonUtilityClasses, buttonVariables } from "@library/forms/buttonStyles";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import {
    absolutePosition,
    borders,
    colorOut,
    unit,
    paddings,
    importantUnit,
    emphasizeLightness,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent } from "csx";
import { forumGlobalVariables } from "@dashboard/compatibilityStyles/forumVariables";

export const buttonCSS = () => {
    const vars = forumGlobalVariables();
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const primary = colorOut(mainColors.primary);

    // @mixin Button
    mixinButton(".Button-Options", ButtonTypes.ICON_COMPACT);

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
    mixinButton(".ButtonGroup.Multi .Button.Handle", ButtonTypes.PRIMARY);
    mixinButton(".ButtonGroup.Multi .Button.Handle .Sprite.SpDropdownHandle", ButtonTypes.PRIMARY);

    cssOut(`.ButtonGroup.Multi .Button.Handle .Sprite.SpDropdownHandle`, {
        width: unit(formElementVars.sizing.height),
        background: important("none"),
        backgroundColor: important("none"),
        ...borders({
            color: "transparent",
        }),
    });

    cssOut(`.ButtonGroup.Multi.Open .Button.Handle`, {
        backgroundColor: colorOut(emphasizeLightness(globalVars.mainColors.primary, 0.2)),
        width: unit(formElementVars.sizing.height),
        ...borders({
            color: "transparent",
        }),
        $nest: {},
    });

    cssOut(`.ButtonGroup.Multi.NewDiscussion`, {
        position: "relative",
        maxWidth: percent(100),
        $nest: {
            "& .Button.Primary": {
                maxWidth: percent(100),
                width: percent(100),
                ...paddings({
                    horizontal: formElementVars.sizing.height,
                }),
            },
            "& .Sprite.SpDropdownHandle": {
                ...absolutePosition.fullSizeOfParent(),
                minWidth: importantUnit(formElementVars.sizing.height),
                padding: important(0),
                border: important(0),
                borderRadius: important(0),
            },
            "& .Button.Handle": {
                ...absolutePosition.middleRightOfParent(),
                width: unit(formElementVars.sizing.height),
                maxWidth: unit(formElementVars.sizing.height),
                minWidth: unit(formElementVars.sizing.height),
                height: unit(formElementVars.sizing.height),
                padding: 0,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                border: important(0),
            },
            "& .Button.Handle .SpDropdownHandle::before": {
                padding: important(0),
            },
        },
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
