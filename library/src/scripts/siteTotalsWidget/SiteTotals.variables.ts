/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/styleUtils";
import { DeepPartial } from "redux";
import { Variables } from "@library/styles/Variables";
import { CSSObject } from "@emotion/css";
import { IThemeVariables } from "@library/theming/themeReducer";
import { media } from "@library/styles/styleShim";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { IconType } from "@vanilla/icons";
import { IBackground } from "@library/styles/cssUtilsTypes";

export enum SiteTotalsAlignment {
    LEFT = "flex-start",
    RIGHT = "flex-end",
    CENTER = "center",
    JUSTIFY = "space-around",
}

export enum SiteTotalsLabelType {
    BOTH = "both",
    ICON = "icon",
    TEXT = "text",
}

export interface ISiteTotalsOptions {
    background?: IBackground;
    alignment: SiteTotalsAlignment;
    textColor?: string;
    formatNumbers?: boolean;
}

export interface ISiteTotalsContainer {
    background?: IBackground;
    alignment: SiteTotalsAlignment;
    textColor?: string;
}

export interface ISiteTotalCount {
    recordType: string;
    label: string;
    iconName: IconType;
    count: number;
    isCalculating: boolean;
    isFiltered: boolean;
}

export interface ISiteTotalApiCount {
    recordType: string;
    label: string;
    isHidden?: boolean;
}

/**
 * @varGroup siteTotals
 * @description Site Totals is a widget that allows the admin to showcase key community metrics in a custom page layout. It gives visitors an idea of how active and engaged users are in the community.
 */
export const siteTotalsVariables = useThemeCache(
    (optionOverrides?: DeepPartial<ISiteTotalsOptions>, forcedVars?: IThemeVariables) => {
        const makeThemeVars = variableFactory("siteTotals");
        const globalVars = globalVariables();

        /**
         * @varGroup siteTotals.options
         */
        const options = makeThemeVars(
            "options",
            {
                /**
                 * @varGroup siteTotals.options.box
                 * @title Site Totals - Box
                 * @expand box
                 */
                box: Variables.box({
                    background: optionOverrides?.background
                        ? optionOverrides.background
                        : { color: globalVars.mainColors.bg.toHexString() },
                }),

                /**
                 * @var siteTotals.options.alignment
                 * @title Site Totals - Alignment
                 * @description Align the totals inside the container.
                 * @type string
                 * @enum flex-start|flex-end|center|space-around
                 */
                alignment: optionOverrides?.alignment ? optionOverrides.alignment : SiteTotalsAlignment.CENTER,

                /**
                 * @var siteTotals.options.mobileAlignment
                 * @title Site Totals - Alignment (Mobile)
                 * @description Align the totals inside the container on mobile. Defaults to match desktop alignment
                 * @type string
                 * @enum flex-start|flex-end|center|space-around
                 */
                mobileAlignment: SiteTotalsAlignment.JUSTIFY,

                /**
                 * @var siteTotals.options.iconColor
                 * @title Site Totals - Icon color
                 * @description Color of the text and icon for each count.
                 * @type string
                 */
                iconColor: optionOverrides?.textColor
                    ? optionOverrides.textColor
                    : globalVars.mainColors.fg.toHexString(),
            },
            optionOverrides,
        );

        /**
         * @varGroup siteTotals.count
         */
        const count = makeThemeVars(
            "count",
            {
                /**
                 * @varGroup siteTotals.count.font
                 * @title Site Totals - Count - Font
                 * @description Font styling for the total count.
                 * @expand font
                 */
                font: Variables.font({
                    color: optionOverrides?.textColor
                        ? optionOverrides.textColor
                        : globalVars.mainColors.fg.toHexString(),
                }),

                /**
                 * @var siteTotals.count.format
                 * @title Site Totals - Count - Format
                 * @description Format the count number into a compressed form.
                 * @type boolean
                 */
                format: optionOverrides?.formatNumbers ? optionOverrides.formatNumbers : false,
            },
            optionOverrides,
        );

        /**
         * @varGroup siteTotals.label
         */
        const label = makeThemeVars(
            "label",
            {
                /**
                 * @varGroup siteTotals.label.font
                 * @title Site Totals - Count - Label
                 * @description Font styleing for the count label.
                 * @expand font
                 */
                font: Variables.font({
                    color: optionOverrides?.textColor
                        ? optionOverrides.textColor
                        : globalVars.mainColors.fg.toHexString(),
                }),
            },
            optionOverrides,
        );

        /**
         * @varGroup siteTotals.breakPoints
         */
        const breakPoints = makeThemeVars("breakpoints", {
            /**
             * @var siteTotals.breakPoints.mobile
             * @type number
             */
            mobile: globalVars.foundationalWidths.breakPoints.xs,
        });

        const mediaQueries = () => {
            const mobile = (styles: CSSObject) => {
                return media({ maxWidth: breakPoints.mobile }, styles);
            };

            return { mobile };
        };

        return {
            mediaQueries,
            options,
            count,
            label,
        };
    },
);
