/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { buttonUtilityClasses } from "@library/forms/buttonStyles";
import { buttonGlobalVariables, buttonVariables } from "@vanilla/library/src/scripts/forms/Button.variables";
import { generateButtonStyleProperties } from "@library/forms/styleHelperButtonGenerator";
import { absolutePosition, importantUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { important, percent, rgba } from "csx";
import { ButtonTypes } from "@library/forms/buttonTypes";

export const buttonCSS = () => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const buttonVars = buttonGlobalVariables();

    // @mixin Button
    mixinButton(".Button-Options", ButtonTypes.ICON_COMPACT);
    mixinButton(".DataList a.Delete.Delete.Delete", ButtonTypes.ICON_COMPACT);
    mixinButton(".MessageList a.Delete.Delete.Delete", ButtonTypes.ICON_COMPACT);

    mixinButton(".Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".FormTitleWrapper .Buttons .Button", ButtonTypes.PRIMARY);
    mixinButton(".FormWrapper .Buttons .Button", ButtonTypes.PRIMARY);
    mixinButton(".FormWrapper .file-upload-browse", ButtonTypes.PRIMARY);
    mixinButton(".FormTitleWrapper .Buttons .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".FormTitleWrapper .file-upload-browse", ButtonTypes.PRIMARY);
    mixinButton(".FormWrapper .Buttons .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".Button-Controls .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".BigButton:not(.Danger)", ButtonTypes.PRIMARY);
    mixinButton(".NewConversation.NewConversation", ButtonTypes.PRIMARY);
    mixinButton(".groupToolbar .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".BoxButtons .ButtonGroup.Multi .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".Section-Members .Group-RemoveMember.Group-RemoveMember", ButtonTypes.PRIMARY);
    mixinButton(".Section-Members .Buttons .Group-RemoveMember.Group-RemoveMember", ButtonTypes.PRIMARY);
    mixinButton(".Section-Members .Buttons .Group-Leader.Group-Leader", ButtonTypes.STANDARD);
    mixinButton(".group-members-filter-box .Button.search", ButtonTypes.PRIMARY);
    mixinButton("#Form_Ban", ButtonTypes.PRIMARY);
    mixinButton(".Popup #UserBadgeForm button", ButtonTypes.PRIMARY);
    mixinButton(".Button.Handle", ButtonTypes.PRIMARY);
    mixinButton("div.Popup .Body .Button.Primary", ButtonTypes.PRIMARY);
    mixinButton(".ButtonGroup.Multi .Button.Handle", ButtonTypes.PRIMARY);
    mixinButton(".ButtonGroup.Multi .Button.Handle .Sprite.SpDropdownHandle", ButtonTypes.PRIMARY);
    mixinButton(".AdvancedSearch .InputAndButton .bwrap .Button", ButtonTypes.PRIMARY);

    const buttonBorderRadius = parseInt(globalVars.borderType.formElements.buttons.toString(), 10);
    const borderOffset = buttonVars.border.width * 2;
    const handleSize = buttonVars.sizing.minHeight - borderOffset;

    if (buttonBorderRadius && buttonBorderRadius > 0) {
        cssOut(`.Frame .ButtonGroup.Multi.NewDiscussion .Button.Handle .SpDropdownHandle::before`, {
            marginTop: styleUnit((formElementVars.sizing.height * 2) / 36), // center vertically
            marginRight: styleUnit(buttonBorderRadius * 0.035), // offset based on border radius. No radius will give no offset.
            maxHeight: styleUnit(handleSize),
            height: styleUnit(handleSize),
            lineHeight: styleUnit(handleSize),
            maxWidth: styleUnit(handleSize),
            minWidth: styleUnit(handleSize),
        });
    }

    cssOut(`.FormWrapper .file-upload-browse, .FormTitleWrapper .file-upload-browse`, {
        marginRight: styleUnit(0),
    });

    cssOut(`.Group-Box .BlockColumn .Buttons a:first-child`, {
        marginRight: styleUnit(globalVars.gutter.quarter),
    });

    cssOut(`.Frame .ButtonGroup.Multi .Button.Handle .Sprite.SpDropdownHandle`, {
        height: styleUnit(handleSize),
        maxHeight: styleUnit(handleSize),
        width: styleUnit(handleSize),
        background: important("transparent"),
        backgroundColor: important("none"),
        ...Mixins.border({
            color: rgba(0, 0, 0, 0),
        }),
        maxWidth: styleUnit(handleSize),
        minWidth: styleUnit(handleSize),
    });

    cssOut(`.Frame .ButtonGroup.Multi.NewDiscussion .Button.Handle.Handle`, {
        position: "absolute",
        top: styleUnit(0),
        right: styleUnit(formElementVars.border.width),
        bottom: styleUnit(0),
        minWidth: importantUnit(handleSize),
        maxWidth: importantUnit(handleSize),
        maxHeight: importantUnit(handleSize),
        minHeight: importantUnit(handleSize),
        height: importantUnit(handleSize),
        width: importantUnit(handleSize),
        borderTopRightRadius: styleUnit(buttonBorderRadius),
        borderBottomRightRadius: styleUnit(buttonBorderRadius),
        display: "block",
    });

    cssOut(`.Frame .ButtonGroup.Multi.Open .Button.Handle`, {
        width: styleUnit(formElementVars.sizing.height),
    });

    cssOut(`.Frame .ButtonGroup.Multi.NewDiscussion .Sprite.SpDropdownHandle`, {
        ...absolutePosition.fullSizeOfParent(),
        padding: important(0),
        border: important(0),
        borderRadius: important(0),
        minWidth: styleUnit(handleSize),
    });

    cssOut(`.ButtonGroup.Multi.NewDiscussion`, {
        position: "relative",
        maxWidth: percent(100),
        boxSizing: "border-box",
        ...{
            ".Button.Primary": {
                maxWidth: percent(100),
                width: percent(100),
                ...Mixins.padding({
                    horizontal: formElementVars.sizing.height,
                }),
            },
            ".Button.Handle": {
                ...absolutePosition.middleRightOfParent(),
                width: styleUnit(formElementVars.sizing.height),
                maxWidth: styleUnit(formElementVars.sizing.height),
                minWidth: styleUnit(formElementVars.sizing.height),
                height: styleUnit(formElementVars.sizing.height),
                padding: 0,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                border: important(0),
                borderTopLeftRadius: important(0),
                borderBottomLeftRadius: important(0),
            },
            ".Button.Handle .SpDropdownHandle::before": {
                padding: important(0),
            },
        },
    });

    cssOut(`.ButtonGroup.Multi > .Button:first-child`, {
        borderTopLeftRadius: styleUnit(buttonBorderRadius),
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

    cssOut(`.AdvancedSearch .InputAndButton .bwrap.bwrap`, {
        ...absolutePosition.topRight(),
    });

    cssOut(`.AdvancedSearch .InputAndButton .bwrap .Button`, {
        minWidth: "auto",
        borderTopLeftRadius: important(0),
        borderBottomLeftRadius: important(0),
    });

    cssOut(`.AdvancedSearch .InputAndButton .bwrap .Button .Sprite.SpSearch`, {
        width: "auto",
        height: "auto",
    });
};

export const mixinButton = (selector: string, buttonType: ButtonTypes = ButtonTypes.STANDARD) => {
    const vars = buttonVariables();
    selector = trimTrailingCommas(selector);

    if (buttonType === ButtonTypes.PRIMARY) {
        cssOut(selector, generateButtonStyleProperties({ buttonTypeVars: vars.primary }));
    } else if (buttonType === ButtonTypes.STANDARD) {
        cssOut(selector, generateButtonStyleProperties({ buttonTypeVars: vars.standard }));
    } else if (buttonType === ButtonTypes.ICON_COMPACT) {
        cssOut(selector, buttonUtilityClasses().iconMixin(buttonGlobalVariables().sizing.compactHeight));
    } else {
        new Error(`No support yet for button type: ${buttonType}`);
    }
};
