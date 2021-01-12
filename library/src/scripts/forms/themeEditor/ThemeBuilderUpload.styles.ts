/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { themeBuilderVariables } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { flexHelper, absolutePosition, importantUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { inputMixin } from "@library/forms/inputStyles";

export const themeBuilderUploadClasses = useThemeCache(() => {
    const themeBuilderVars = themeBuilderVariables();
    const globalVars = globalVariables();
    const style = styleFactory("themeBuilderUpload");

    const root = style({
        display: "flex",
        alignItems: "center",
        width: percent(100),
    });

    const button = style("button", {
        flex: 1,
        ...inputMixin(),
        ...flexHelper().middleLeft(),
        minHeight: 0,
        ...Mixins.border(themeBuilderVars.border),
        height: themeBuilderVars.input.height,
        ...Mixins.font(themeBuilderVars.input.fonts),
        cursor: "pointer",
        ...{
            "&:hover, &:focus, &:active": {
                ...Mixins.border({ ...themeBuilderVars.border, color: globalVars.mainColors.primary }),
            },
        },
    });

    const optionContainer = style("optionContainer", {
        ...Mixins.border(themeBuilderVars.border),
        background: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        marginLeft: 4,
        position: "relative",
        width: 36,
        backgroundColor: ColorsUtils.colorOut(themeBuilderVars.border.color),
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
    });
    const optionButton = style("optionButton", {
        height: importantUnit(themeBuilderVars.input.height - 2),
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        ...{
            "&:hover, &:active, &:focus": {
                color: ColorsUtils.colorOut(globalVars.elementaryColors.white, {
                    makeImportant: true,
                }),
                opacity: 0.9,
            },
        },
    });

    const optionDropdown = style("optionDropdown", {
        ...{
            "&&": {
                width: themeBuilderVars.input.width,
                minWidth: 0,
                borderRadius: themeBuilderVars.border.radius,
                marginTop: 2,
                marginBottom: 2,
            },
        },
    });
    const imagePreviewContainer = style("imagePreviewContainer", {
        position: "absolute",
        top: -1,
        left: -1,
        width: `calc(100% + 2px)`,
        height: `calc(100% + 2px)`,
        overflow: "hidden",
        borderRadius: themeBuilderVars.border.radius,
    });
    const imagePreview = style("imagePreview", {
        ...absolutePosition.fullSizeOfParent(),
        filter: "brightness(0.7)",
        objectFit: "cover",
        objectPosition: "center",
    });

    return { root, button, optionContainer, optionButton, optionDropdown, imagePreviewContainer, imagePreview };
});
