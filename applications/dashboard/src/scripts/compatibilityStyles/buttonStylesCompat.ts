/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssOut, trimTrailingCommas, mixinCloseButton } from "@dashboard/compatibilityStyles/index";
import { buttonGlobalVariables, ButtonTypes, buttonUtilityClasses, buttonVariables } from "@library/forms/buttonStyles";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import {
    absolutePosition,
    borders,
    colorOut,
    importantUnit,
    offsetLightness,
    paddings,
    unit,
} from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent, rgba } from "csx";

export const buttonCSS = () => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const mainColors = globalVars.mainColors;
    const primary = colorOut(mainColors.primary);

    // @mixin Button
    mixinButton(".Button-Options", ButtonTypes.ICON_COMPACT);
    mixinButton(".DataList a.Delete.Delete.Delete", ButtonTypes.ICON_COMPACT);
    mixinButton(".MessageList a.Delete.Delete.Delete", ButtonTypes.ICON_COMPACT);

    mixinButton(".Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".FormTitleWrapper .Buttons .Button", ButtonTypes.PRIMARY);
    mixinButton(".FormWrapper .Buttons .Button", ButtonTypes.PRIMARY);
    mixinButton(".FormTitleWrapper .Buttons .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".FormWrapper .Buttons .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".Button-Controls .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".BigButton:not(.Danger)", ButtonTypes.PRIMARY);
    mixinButton(".NewConversation.NewConversation", ButtonTypes.PRIMARY);
    mixinButton(".groupToolbar .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".BoxButtons .ButtonGroup.Multi .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".Section-Members .Group-RemoveMember", ButtonTypes.PRIMARY);
    mixinButton(".group-members-filter-box .Button.search", ButtonTypes.PRIMARY);
    mixinButton("#Form_Ban", ButtonTypes.PRIMARY);
    mixinButton(".Popup #UserBadgeForm button", ButtonTypes.PRIMARY);
    mixinButton(".Button.Handle", ButtonTypes.PRIMARY);
    mixinButton("div.Popup .Body .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".ButtonGroup.Multi .Button.Handle", ButtonTypes.PRIMARY);

    const buttonBorderRadius = parseInt(formElementVars.border.radius.toString(), 10);
    const borderOffset = globalVars.border.width * 2;
    const handleSize = formElementVars.sizing.height - borderOffset;

    if (buttonBorderRadius && buttonBorderRadius > 0) {
        cssOut(`.ButtonGroup.Multi.NewDiscussion .Button.Handle .SpDropdownHandle::before`, {
            marginTop: unit((formElementVars.sizing.height * 2) / 36), // center vertically
            marginRight: unit(buttonBorderRadius * 0.035), // offset based on border radius. No radius will give no offset.
            maxHeight: unit(handleSize),
            height: unit(handleSize),
            lineHeight: unit(handleSize),
        });
    }

    cssOut(`.ButtonGroup.Multi .Button.Handle .Sprite.SpDropdownHandle`, {
        height: unit(handleSize),
        maxHeight: unit(handleSize),
        width: unit(handleSize),
        maxWidth: unit(handleSize),
        background: important("transparent"),
        backgroundColor: important("none"),
        ...borders({
            color: rgba(0, 0, 0, 0),
        }),
    });

    cssOut(`.ButtonGroup.Multi.NewDiscussion .Button.Handle.Handle`, {
        top: unit(0),
        right: unit(formElementVars.border.width),
        minWidth: importantUnit(handleSize),
        maxWidth: importantUnit(handleSize),
        maxHeight: importantUnit(handleSize),
        minHeight: importantUnit(handleSize),
        height: importantUnit(handleSize),
        width: importantUnit(handleSize),
        borderTopRightRadius: unit(formElementVars.border.radius),
        borderBottomRightRadius: unit(formElementVars.border.radius),
    });

    cssOut(`.ButtonGroup.Multi.Open .Button.Handle`, {
        backgroundColor: colorOut(offsetLightness(globalVars.mainColors.primary, 0.2)),
        width: unit(formElementVars.sizing.height),
    });

    cssOut(`.ButtonGroup.Multi.NewDiscussion`, {
        position: "relative",
        maxWidth: percent(100),
        boxSizing: "border-box",
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
                borderTopLeftRadius: important(0),
                borderBottomLeftRadius: important(0),
            },
            "& .Button.Handle .SpDropdownHandle::before": {
                padding: important(0),
            },
        },
    });

    cssOut(`.ButtonGroup.Multi > .Button:first-child`, {
        borderTopLeftRadius: unit(formElementVars.border.radius),
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
    mixinButton(".viewPollResults", ButtonTypes.STANDARD);

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
