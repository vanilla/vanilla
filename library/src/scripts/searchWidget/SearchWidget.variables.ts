/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { DeepPartial } from "redux";
import { Variables } from "@library/styles/Variables";
import { IBoxOptions, ISpacing } from "@library/styles/cssUtilsTypes";
import { IThemeVariables } from "@library/theming/themeReducer";
import { BorderType } from "@library/styles/styleHelpers";
import { pageHeadingBoxVariables } from "@library/layout/PageHeadingBox.variables";

export interface ISearchWidgetOptions {
    box: IBoxOptions;
    container: {
        spacing: ISpacing;
        spacingMobile: ISpacing;
    };
    headerAlignment?: "left" | "center";
}

/**
 * @varGroup searchWidget
 * @description User Spotlight is a component of a user with user data and description.
 */
export const searchWidgetVariables = useThemeCache(
    (optionOverrides?: DeepPartial<ISearchWidgetOptions>, forcedVars?: IThemeVariables) => {
        const makeThemeVars = variableFactory("searchWidget");
        const pageHeadingVars = pageHeadingBoxVariables();
        const globalVars = globalVariables();

        /**
         * @varGroup searchWidget.options
         */
        const options: ISearchWidgetOptions = makeThemeVars(
            "options",
            {
                /**
                 * @varGroup searchWidget.options.box
                 * @title User Spotlight - Box
                 * @expand box
                 */
                box: Variables.box({
                    borderType: BorderType.SHADOW,
                    border: {
                        radius: globalVars.border.radius,
                    },
                }),

                /**
                 * @varGroup searchWidget.options.container
                 * @title User Spotlight - Container
                 */
                container: {
                    /**
                     * @var searchWidget.options.container.spacing
                     * @expand spacing
                     */
                    spacing: Variables.spacing({
                        bottom: 48,
                    }),

                    /**
                     * @var searchWidget.options.container.spacingMobile
                     * @expand spacingMobile
                     */
                    spacingMobile: Variables.spacing({}),
                },
                headerAlignment: pageHeadingVars.options.alignment as "left" | "center",
            },
            optionOverrides,
        );

        return {
            options,
        };
    },
);
