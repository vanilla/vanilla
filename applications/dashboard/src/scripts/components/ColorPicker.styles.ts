/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { translateX } from "csx";
import { negativeUnit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { css } from "@emotion/css";

export const colorPickerClasses = () => {
    const globalVars = globalVariables();

    const root = css({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "stretch",
    });

    const swatch = css({
        display: "block",
        position: "relative",
        width: 40,
        flexBasis: 40,
        borderTopLeftRadius: 0,
        borderBottomLeftRadius: 0,
        borderTopRightRadius: 4,
        borderBottomRightRadius: 4,
        borderTopColor: globalVars.border.color.toString(),
        borderBottomColor: globalVars.border.color.toString(),
        borderRightColor: globalVars.border.color.toString(),
        ...{
            "&:focus, &:active": {
                zIndex: 2,
            },
        },
    });

    const realInput = css({
        position: "absolute",
        outline: 0,
        transform: translateX(negativeUnit(10)),
    });

    const clearButton = css({
        position: "absolute",
        right: 54,
        height: 30,
        color: "#777a80",
    });

    return {
        root,
        swatch,
        realInput,
        clearButton,
    };
};
