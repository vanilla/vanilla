/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { buttonGlobalVariables, buttonVariables } from "@library/forms/Button.variables";
import { buttonResetMixin, buttonSizing } from "@library/forms/buttonMixins";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { IButton } from "@library/forms/styleHelperButtonInterface";
import { Mixins } from "@library/styles/Mixins";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { percent } from "csx";
import merge from "lodash/merge";
import { css, CSSObject } from "@emotion/css";
import cloneDeep from "lodash/cloneDeep";
import { defaultTransition } from "@library/styles/styleHelpersAnimation";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const generateButtonStyleProperties = (props: {
    buttonTypeVars: IButton;
    setZIndexOnState?: boolean;
    stateSuffix?: string;
    debug?: boolean | string;
}) => {
    const { setZIndexOnState = false, stateSuffix, debug = false } = props;
    const formElementVars = formElementsVariables();
    const buttonGlobalVars = buttonGlobalVariables();

    const zIndex = setZIndexOnState ? 1 : undefined;

    const { buttonTypeVars } = props;

    const defaultBorder = Mixins.border(buttonTypeVars.borders, {
        fallbackBorderVariables: buttonGlobalVars.border,
        debug,
    });

    const hoverBorder = buttonTypeVars.hover?.borders
        ? merge(
              cloneDeep(defaultBorder),
              Mixins.border({ ...buttonTypeVars.hover.borders }, { fallbackBorderVariables: buttonGlobalVars.border }),
          )
        : {};

    const activeBorder = buttonTypeVars.active?.borders
        ? merge(
              cloneDeep(defaultBorder),
              Mixins.border({ ...buttonTypeVars.active.borders }, { fallbackBorderVariables: buttonGlobalVars.border }),
          )
        : {};

    const focusBorder = buttonTypeVars.focus?.borders
        ? merge(
              cloneDeep(defaultBorder),
              Mixins.border(
                  { ...(buttonTypeVars.focus && buttonTypeVars.focus.borders) },
                  { fallbackBorderVariables: buttonGlobalVars.border },
              ),
          )
        : defaultBorder;

    const focusAccessibleBorder = buttonTypeVars.focusAccessible?.borders
        ? merge(
              cloneDeep(defaultBorder),
              Mixins.border(
                  { ...buttonTypeVars.focusAccessible.borders },
                  { fallbackBorderVariables: buttonGlobalVars.border },
              ),
          )
        : {};

    const disabledBorder = buttonTypeVars.disabled?.borders
        ? merge(
              cloneDeep(defaultBorder),
              Mixins.border(
                  { ...buttonTypeVars.disabled.borders },
                  { fallbackBorderVariables: buttonGlobalVars.border },
              ),
          )
        : {};

    const disabledOpacity = buttonTypeVars.disabled?.opacity;
    const disabledStyle: CSSObject = {
        opacity: disabledOpacity,
        color: ColorsUtils.colorOut(buttonTypeVars.disabled?.colors?.fg ?? undefined),
        backgroundColor: ColorsUtils.colorOut(buttonTypeVars.disabled?.colors?.bg ?? undefined),
        ...disabledBorder,
    };

    const result: CSSObject = {
        ...buttonResetMixin(),
        textOverflow: "ellipsis",
        overflow: "hidden",
        width: "auto",
        maxWidth: percent(100),
        backgroundColor: ColorsUtils.colorOut(buttonTypeVars.colors?.bg),
        ...Mixins.font({ ...buttonTypeVars.fonts, color: buttonTypeVars.colors?.fg ?? buttonTypeVars.fonts?.color }),
        WebkitFontSmoothing: "antialiased",
        ...defaultBorder,
        ...buttonSizing({
            minHeight: buttonTypeVars.sizing?.minHeight,
            minWidth: buttonTypeVars.sizing?.minWidth,
            fontSize: buttonTypeVars.fonts?.size,
            paddingHorizontal: buttonTypeVars.padding?.horizontal,
            formElementVars: formElementVars,
            borderRadius: buttonTypeVars.borders?.radius,
            skipDynamicPadding: buttonTypeVars.skipDynamicPadding ?? false,
        }),
        display: "inline-flex",
        alignItems: "center",
        position: "relative",
        textAlign: "center",
        whiteSpace: "nowrap",
        verticalAlign: "middle",
        justifyContent: "center",
        touchAction: "manipulation",
        cursor: "pointer",
        opacity: buttonTypeVars.opacity,
        ...(buttonTypeVars.useShadow ? shadowHelper().button() : { boxShadow: "none" }),
        ...defaultTransition("background", "color", "border"),
        ...{
            [`&:not([disabled]):not(.focus-visible)`]: {
                outline: 0,
            },
            [`&:not([disabled]):hover${stateSuffix ?? ""}`]: {
                zIndex,
                opacity: buttonTypeVars.hover?.opacity,
                color: ColorsUtils.colorOut(buttonTypeVars.hover?.colors?.fg),
                backgroundColor: ColorsUtils.colorOut(buttonTypeVars.hover?.colors?.bg),
                ...hoverBorder,
                ...(buttonTypeVars.useShadow ? shadowHelper().buttonHover() : { boxShadow: "none" }),
            },
            [`&:not([disabled]):focus${stateSuffix ?? ""}`]: {
                zIndex,
                opacity: buttonTypeVars.focus?.opacity,
                color: ColorsUtils.colorOut(buttonTypeVars.focus?.colors?.fg),
                backgroundColor: ColorsUtils.colorOut(buttonTypeVars.focus?.colors?.bg),
                ...focusBorder,
            },
            [`&:not([disabled]):active${stateSuffix ?? ""}`]: {
                zIndex,
                opacity: buttonTypeVars.active?.opacity,
                color: ColorsUtils.colorOut(buttonTypeVars.active?.colors?.fg),
                backgroundColor: ColorsUtils.colorOut(buttonTypeVars.active?.colors?.bg),
                ...activeBorder,
            },
            [`&:not([disabled]):focus-visible${stateSuffix ?? ""}`]: {
                zIndex,
                opacity: buttonTypeVars.focusAccessible?.opacity,
                color: ColorsUtils.colorOut(buttonTypeVars.focusAccessible?.colors?.fg),
                backgroundColor: ColorsUtils.colorOut(buttonTypeVars.focusAccessible?.colors?.bg),
                ...focusAccessibleBorder,
            },
            "&[disabled]": disabledStyle,
            ...(buttonTypeVars.extraNested ?? {}),
        },
    };

    return result;
};

const generateButtonClass = (
    buttonTypeVars: IButton,
    options?: { setZIndexOnState?: boolean; debug?: boolean | string },
) => {
    const { setZIndexOnState = false, debug = false } = options || {};
    const buttonStyles = generateButtonStyleProperties({
        buttonTypeVars,
        setZIndexOnState,
    });
    const buttonClass = css({ ...buttonStyles, label: `button-${buttonTypeVars.name}` });
    return buttonClass;
};

export default generateButtonClass;
