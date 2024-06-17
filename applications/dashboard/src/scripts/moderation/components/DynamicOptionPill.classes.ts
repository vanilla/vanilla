/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export const dynamicOptionPillClasses = (isActive: boolean) => {
    const root = css({
        display: "inline-flex",
        padding: 4,
        background: ColorsUtils.colorOut(globalVariables().elementaryColors.white),
        borderRadius: "100px",
        border: "1px solid #dddee0",
        transition: "all ease 250ms",
        "&.checked": {
            borderColor: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
            background: ColorsUtils.colorOut(globalVariables().elementaryColors.primary.fade(0.1)),
            color: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
            "&:hover, &:active, &:focus, &.focus-visible": {
                borderColor: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
                color: ColorsUtils.colorOut(globalVariables().elementaryColors.white),
                backgroundColor: ColorsUtils.colorOut(globalVariables().elementaryColors.primary.fade(0.5)),
            },
        },
        "&:hover, &:active, &:focus, &.focus-visible": {
            borderColor: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
            color: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
            backgroundColor: ColorsUtils.colorOut(globalVariables().elementaryColors.primary.fade(0.08)),
        },
        "&:first-child": {
            marginTop: -8,
        },
        "&:not(:first-child)": {
            marginTop: 8,
        },
    });
    const label = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        gap: 8,
        cursor: "pointer",
    });
    const input = css({});
    const photo = css({
        flexShrink: 0,
        background: "white",
        transition: "border linear 250ms",
        border: "1px solid currentColor",
        opacity: 0.5,
        ...(isActive && {
            opacity: 1,
        }),
    });
    const name = css({
        ...(!isActive && {
            color: ColorsUtils.colorOut(globalVariables().elementaryColors.darkText.fade(0.6)),
        }),
        fontWeight: globalVariables().fonts.weights.normal,
        "&:first-child": {
            paddingInlineStart: 6,
        },
        wordBreak: "break-all",
        textWrap: "pretty",
    });
    const removeButton = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "0 4px",
        "& svg": {
            maxHeight: 10,
            aspectRatio: "1 / 1",
            opacity: 0.5,
            ...(isActive && {
                opacity: 1,
            }),
        },
    });
    return {
        root,
        label,
        input,
        photo,
        name,
        removeButton,
    };
};
