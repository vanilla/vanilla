/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent, px } from "csx";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { flexHelper } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { iconVariables } from "@library/icons/iconStyles";
import { Mixins } from "@library/styles/Mixins";

export const locationPickerClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("locationPicker");

    const root = style({});

    const articlePlaceholder = style("articlePlaceholder", {
        display: "block",
        width: percent(100),
        height: px(24),
        border: `dotted 1px ${globalVars.mixBgAndFg(0.5).toString()}`,
        margin: `${px(6)} ${px(12)}`,
        borderRadius: px(2),
        ...{
            "&:hover": {
                backgroundColor: globalVars.mainColors.primary.fade(0.1).toString(),
            },
            "&:focus": {
                backgroundColor: globalVars.mainColors.primary.fade(0.1).toString(),
            },
            "&.focus-visible": {
                backgroundColor: globalVars.mainColors.primary.fade(0.8).toString(),
                borderColor: globalVars.mainColors.fg.toString(),
                borderStyle: "solid",
            },
            "&.isActive": {
                backgroundColor: globalVars.mixPrimaryAndBg(0.08).toString(),
                ...Mixins.padding({
                    vertical: 0,
                    horizontal: 2,
                }),
                ...flexHelper().middleLeft(),
            },
            "&.isFirst": {
                marginTop: px(18),
            },
            "&.isLast": {
                marginBottom: px(18),
            },
        },
    });

    const checkMark = style("checkMark", {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        height: 20,
        width: 20,
        marginRight: 4,
    });

    const instructions = style("instructions", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium"),
        }),
        padding: `${px(8)} ${px(12)}`,
        width: percent(100),
        minHeight: styleUnit(50),
    });

    const iconVars = iconVariables();

    const iconWrapper = style("iconWrapper", {
        display: "inline-flex",
        ...Mixins.margin({
            right: ".4em",
            bottom: "-.05em",
        }),
        flexBasis: styleUnit(iconVars.categoryIcon.width),
    });

    const initialText = style("initialText", {
        whiteSpace: "nowrap",
        paddingLeft: "0.2em",
    });

    return {
        root,
        articlePlaceholder,
        checkMark,
        instructions,
        iconWrapper,
        initialText,
    };
});
