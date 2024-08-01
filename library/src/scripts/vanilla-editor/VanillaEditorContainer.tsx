import { css } from "@emotion/css";
import { inputVariables } from "@library/forms/inputStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cx } from "@library/styles/styleShim";
import React from "react";

/**
 * Container for the editor and its static toolbar.
 */

export function VanillaEditorContainer(props: { children: React.ReactNode; className?: string; boxShadow?: boolean }) {
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const { boxShadow = false } = props;
    const baseStyles = css({
        display: "flex",
        flexDirection: "column",
        justifyContent: "space-between",
        minHeight: 200,
        borderRadius: inputVars.border.radius,
        ...(boxShadow && {
            boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(inputVars.border.color)}`,
            // Border can get clipped by inner contents so use a box shadow instead.
            ":focus-within": {
                boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`,
            },
        }),
    });
    return <div className={cx(baseStyles, props.className)}>{props.children}</div>;
}
