/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { inputVariables } from "@library/forms/inputStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const durationPickerClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const inputVars = inputVariables();

    const root = css({
        display: "flex",
        alignItems: "stretch",
        justifyContent: "start",
    });

    const lengthInputWrap = css({
        "&&": {
            ...Mixins.margin({ all: 0 }),
        },
    });

    const lengthInput = css({
        maxWidth: "8ch",
    });

    const lengthInputBox = css({
        "&&": {
            borderRight: 0,
            borderTopRightRadius: 0,
            borderBottomRightRadius: 0,
            textAlign: "right",
            "&&:focus, &&:active, &&:hover, &&.focus-visible": {
                borderTopRightRadius: 0,
                borderBottomRightRadius: 0,
                marginLeft: 1,
                position: "relative",
                zIndex: 1,
            },
        },
    });

    const unitInput = css({
        "&&": {
            ...Mixins.margin({ all: 0 }),
            flex: 1,
            borderTopLeftRadius: 0,
            borderBottomLeftRadius: 0,
            "&&:focus, &&:active, &&:hover, &&:focus-within": {
                borderTopLeftRadius: 0,
                borderBottomLeftRadius: 0,
            },
        },
    });

    const unitInputWithButton = css({
        "&&": {
            borderRadius: 0,
            "&&:focus, &&:active, &&:hover, &&:focus-within": {
                borderRadius: 0,
            },
        },
    });

    const button = css({
        ...Mixins.margin({ all: 0 }),
        minWidth: "fit-content",
        borderTopLeftRadius: 0,
        borderBottomLeftRadius: 0,
        borderLeft: 0,
        borderColor: ColorsUtils.colorOut(inputVars.border.color),
        "&&:focus, &&:active, &&:hover": {
            borderTopLeftRadius: 0,
            borderBottomLeftRadius: 0,
            borderLeft: 0,
        },
    });

    return { root, lengthInputWrap, lengthInput, lengthInputBox, unitInput, unitInputWithButton, button };
});
