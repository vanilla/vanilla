/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { buttonVariables } from "@library/forms/Button.variables";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { newPostMenuVariables } from "@library/newPostMenu/NewPostMenu.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";
import { DeepPartial } from "redux";

export const newPostMenuDropdownClasses = useThemeCache(
    (containerOptions?: DeepPartial<IHomeWidgetContainerOptions>) => {
        const vars = newPostMenuVariables();
        const globalVars = globalVariables();
        const buttonVars = buttonVariables();

        const container = css({
            display: "flex",
            flexDirection: "column",
            ...Mixins.box({
                ...Variables.box({
                    borderType: containerOptions?.borderType,
                }),
            }),
            backgroundColor: containerOptions?.outerBackground?.color
                ? ColorsUtils.colorOut(containerOptions?.outerBackground?.color)
                : undefined,
        });

        const newPostButtonBorderAndShadow = {
            ...Mixins.border(vars.button.border),
            ...shadowHelper().dropDown(),
        };

        const button = (borderRadius?: string | number) =>
            css({
                "&&": {
                    minWidth: buttonVars.primary.sizing?.minWidth ?? styleUnit(148),
                    maxWidth: "fit-content",
                    height: styleUnit(48),
                    ...newPostButtonBorderAndShadow,
                    borderRadius: borderRadius ? styleUnit(borderRadius) : undefined,
                    ...{
                        [`&:not([disabled]):focus-visible, &:not([disabled]):focus, &:not([disabled]):hover, &:not([disabled]):active`]:
                            {
                                ...newPostButtonBorderAndShadow,
                                borderRadius: borderRadius ? styleUnit(borderRadius) : undefined,
                            },
                    },
                },
            });

        const separateButton = css({
            "&:not(:first-child)": {
                marginTop: 16,
            },
        });

        const buttonContents = css({
            display: "flex",
            justifyContent: "space-around",
            alignItems: "center",
            width: "100%",
            "& svg": {
                margin: 0,
                color: ColorsUtils.colorOut(vars.button.font.color),
            },
        });

        const buttonIcon = css({
            display: "flex",
            marginRight: styleUnit(4),
        });

        const buttonLabel = css({
            ...Mixins.font(vars.button.font),
        });

        const buttonDropdownContents = css({
            ...Mixins.padding({ vertical: globalVars.gutter.half }),
            ...Mixins.margin({ top: 4 }),

            //instead of importing "@reach/menu-button/styles.css", extracted some styles
            //from their github repo https://github.com/reach/reach-ui/blob/develop/packages/menu-button/styles.css
            "&[data-reach-menu-list]": {
                outline: "none",
            },

            "& [data-reach-menu-item][data-selected]": {
                backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
                color: globalVars.states.hover.contrast
                    ? ColorsUtils.colorOut(globalVars.states.hover.contrast)
                    : undefined,
            },
        });

        return {
            container,
            separateButton,
            button,
            buttonContents,
            buttonIcon,
            buttonLabel,
            buttonDropdownContents,
        };
    },
);
