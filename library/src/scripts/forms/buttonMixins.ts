/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { CSSObject } from "@emotion/css";
import { important, px } from "csx";
import { paddingOffsetBasedOnBorderRadius } from "@library/forms/paddingOffsetFromBorderRadius";
import { buttonGlobalVariables } from "./Button.variables";

export const buttonSizing = (props: {
    minHeight;
    minWidth;
    fontSize;
    paddingHorizontal;
    formElementVars;
    borderRadius;
    skipDynamicPadding;
    debug?: boolean;
}) => {
    const buttonGlobals = buttonGlobalVariables();
    const {
        minHeight = buttonGlobals.sizing.minHeight,
        minWidth = buttonGlobals.sizing.minWidth,
        fontSize = buttonGlobals.font.size,
        paddingHorizontal = buttonGlobals.padding.horizontal,
        formElementVars,
        borderRadius,
        skipDynamicPadding,
        debug = false,
    } = props;

    const borderWidth = formElementVars.borders ? formElementVars.borders : buttonGlobals.border.width;
    const height = minHeight ?? formElementVars.sizing.minHeight;

    const paddingOffsets = !skipDynamicPadding
        ? paddingOffsetBasedOnBorderRadius({
              radius: borderRadius,
              extraPadding: buttonGlobals.padding.fullBorderRadius.extraHorizontalPadding,
              height,
          })
        : {
              right: 0,
              left: 0,
          };

    return {
        minHeight: styleUnit(height),
        minWidth: minWidth ? styleUnit(minWidth) : undefined,
        fontSize: styleUnit(fontSize),
        padding: `0px ${px(paddingHorizontal + (paddingOffsets.right || 0) ?? 0)} 0px ${px(
            paddingHorizontal + (paddingOffsets.left || 0) ?? 0,
        )}`,
        lineHeight: styleUnit(height - borderWidth * 2),
    };
};

export const buttonResetMixin = (): CSSObject => ({
    ...userSelect(),
    appearance: "none",
    border: 0,
    padding: 0,
    background: "none",
    cursor: "pointer",
    color: "inherit",
    textDecoration: important("none"),
    textAlign: "inherit",
    overflowWrap: "break-word",
});
