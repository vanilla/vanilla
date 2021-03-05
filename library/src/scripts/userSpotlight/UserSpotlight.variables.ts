/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { DeepPartial } from "redux";
import { Variables } from "@library/styles/Variables";
import { CSSObject } from "@emotion/css";
import { IBoxOptions, ISpacing } from "@library/styles/cssUtilsTypes";
import { IThemeVariables } from "@library/theming/themeReducer";
import { BorderType } from "@library/styles/styleHelpers";
import { media } from "../styles/styleShim";

export interface IUserSpotlightOptions {
    box: IBoxOptions;
    container: {
        noGutter?: boolean;
        spacing: ISpacing;
    };
    userTextAlignment: "left" | "right";
}

/**
 * @varGroup userSpotlight
 * @description User Spotlight is a component of a user with user data and description.
 */
export const userSpotlightVariables = useThemeCache(
    (optionOverrides?: DeepPartial<IUserSpotlightOptions>, forcedVars?: IThemeVariables) => {
        const makeThemeVars = variableFactory("userSpotlight");
        const globalVars = globalVariables();

        /**
         * @varGroup userSpotlight.options
         */
        const options: IUserSpotlightOptions = makeThemeVars(
            "options",
            {
                /**
                 * @varGroup userSpotlight.options.box
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
                 * @varGroup userSpotlight.options.container
                 * @title User Spotlight - Container
                 */
                container: {
                    /**
                     * @var userSpotlight.options.container.noGutter
                     * @description Whether to wrap or no in container, by default there is no gutter
                     * @type boolean
                     */
                    noGutter: true,

                    /**
                     * @var userSpotlight.options.container.spacing
                     * @expand spacing
                     */
                    spacing: Variables.spacing({}),
                },

                /**
                 * @var userSpotlight.options.userTextAlignment
                 * @description Whether user name and user title aligned left or right.
                 */
                userTextAlignment: "left" as "left" | "right",
            },
            optionOverrides,
        );

        /**
         * @varGroup userSpotlight.breakPoints
         */
        const breakPoints = makeThemeVars("breakPoints", {
            mobile: globalVars.foundationalWidths.breakPoints.xs,
        });

        const mediaQueries = () => {
            const mobile = (styles: CSSObject) => {
                return media({ maxWidth: breakPoints.mobile }, styles);
            };

            return { mobile };
        };

        /**
         * @varGroup userSpotlight.avatarContainer
         */
        const avatarContainer = makeThemeVars("avatarContainer", {
            /**
             * @varGroups userSpotlight.avatar.sizing
             * @title Avatar Container Sizing
             * @description Width and height of avatar container, normally used when there is a background image for avatar.
             */
            sizing: {
                width: undefined as number | string | undefined,
                height: undefined as number | string | undefined,
            },

            /**
             * @varGroups userSpotlight.avatarContainer.sizing
             * @title Avatar Container Sizing Mobile
             * @description Width and height of avatar container on mobile, normally used when there is a background image for avatar.
             */
            sizingMobile: {
                width: undefined as number | string | undefined,
                height: undefined as number | string | undefined,
            },

            /**
             * @varGroup userSpotlight.avatarContainer.padding
             * @expand spacing
             */
            padding: Variables.spacing({
                right: 16,
            }),

            /**
             * @varGroup userSpotlight.avatarContainer.paddingMobile
             * @expand spacing
             */
            paddingMobile: Variables.spacing({}),

            /**
             * @varGroup userSpotlight.avatarContainer.margin
             * @expand spacing
             */
            margin: Variables.spacing({}),

            /**
             * @varGroup userSpotlight.avatarContainer.marginMobile
             * @expand spacing
             */
            marginMobile: Variables.spacing({}),

            /**
             * @var userSpotlight.avatarContainer.bgImage
             * @title Avatar Container Background Image
             * @description By default no background image.
             * @type string
             */
            bgImage: undefined as string | undefined,

            /**
             * @var userSpotlight.avatarContainer.bgPosition
             * @title Avatar Container Background Image
             * @type string
             */
            bgPosition: undefined as string | undefined,

            /**
             * @var userSpotlight.avatarContainer.bg
             * @description By default transparent.
             * @title Avatar Container Background Position
             * @type string
             */
            bg: globalVars.elementaryColors.transparent,
        });

        /**
         * @varGroup userSpotlight.avatarLink
         */
        const avatarLink = makeThemeVars("avatarLink", {
            /**
             * @varGroup userSpotlight.avatarLink.padding
             * @expand spacing
             */
            padding: Variables.spacing({}),

            /**
             * @var userSpotlight.avatarLink.display
             */
            display: "inline-flex",
        });

        /**
         * @varGroup userSpotlight.avatar
         */
        const avatar = makeThemeVars("avatar", {
            /**
             * @varGroups userSpotlight.avatar.border
             * @expand border
             */
            border: Variables.border({
                radius: 120,
            }),

            /**
             * @varGroup userSpotlight.avatar.sizing
             * @title Avatar Sizing
             * @description Width and height of avatar
             */
            sizing: {
                width: 80,
                height: 80,
            },

            /**
             * @varGroup userSpotlight.avatar.sizingMobile
             * @title Avatar Sizing Mobile
             * @description Width and height of avatar on smaller screens.
             */
            sizingMobile: {
                width: 60,
                height: 60,
            },
        });

        /**
         * @varGroup userSpotlight.textContainer
         */
        const textContainer = makeThemeVars("textContainer", {
            /**
             * @varGroup userSpotlight.textContainer.spacing
             * @expand spacing
             */
            spacing: Variables.spacing({}),

            /**
             * @varGroup userSpotlight.textContainer.spacingMobile
             * @expand spacing
             */
            spacingMobile: Variables.spacing({}),

            /**
             * @varGroup userSpotlight.textContainer.font
             * @expand font
             */
            font: Variables.font({}),

            /**
             * @varGroup userSpotlight.textContainer.fontMobile
             * @expand font
             */
            fontMobile: Variables.font({}),
        });

        /**
         * @varGroup userSpotlight.title
         */
        const title = makeThemeVars("title", {
            /**
             * @varGroup userSpotlight.title.font
             * @expand font
             */
            font: Variables.font({
                size: globalVars.fonts.size.subTitle,
                weight: globalVars.fonts.weights.bold,
                color: globalVars.mainColors.fg,
            }),

            /**
             * @varGroup userSpotlight.title.fontMobile
             * @expand font
             */
            fontMobile: Variables.font({}),

            /**
             * @varGroup userSpotlight.title.spacing
             * @expand spacing
             */
            spacing: Variables.spacing({
                bottom: 10,
            }),

            /**
             * @varGroup userSpotlight.title.spacingMobile
             * @expand spacing
             */
            spacingMobile: Variables.spacing({}),
        });

        /**
         * @varGroup userSpotlight.description
         */
        const description = makeThemeVars("description", {
            /**
             * @varGroup userSpotlight.description.font
             * @expand font
             */
            font: Variables.font({
                color: globalVars.mainColors.fg,
            }),

            /**
             * @varGroup userSpotlight.description.spacing
             * @expand spacing
             */
            spacing: Variables.spacing({
                bottom: 10,
            }),
        });

        /**
         * @varGroup userSpotlight.userText
         */
        const userText = makeThemeVars("userText", {
            /**
             * @varGroup userSpotlight.userText.font
             * @expand font
             */
            font: Variables.font({
                color: globalVars.mainColors.fg,
            }),

            /**
             * @varGroup userSpotlight.userText.fontMobile
             * @expand font
             */
            fontMobile: Variables.font({}),

            /**
             * @varGroup userSpotlight.userText.padding
             * @expand spacing
             */
            padding: Variables.spacing({}),

            /**
             * @varGroup userSpotlight.userText.paddingMobile
             * @expand spacing
             */
            paddingMobile: Variables.spacing({}),

            /**
             * @varGroup userSpotlight.userText.margin
             * @expand spacing
             */
            margin: Variables.spacing({
                top: "auto",
            }),
        });

        /**
         * @varGroup userSpotlight.userName
         */
        const userName = makeThemeVars("userName", {
            /**
             * @varGroup userSpotlight.userName.font
             * @expand font
             */
            font: Variables.font({
                color: globalVars.mainColors.fg,
                weight: globalVars.fonts.weights.bold,
            }),

            /**
             * @varGroup userSpotlight.userName.fontMobile
             * @expand font
             */
            fontMobile: Variables.font({}),
        });

        /**
         * @varGroup userSpotlight.userTitle
         */
        const userTitle = makeThemeVars("userTitle", {
            /**
             * @varGroup userSpotlight.userTitle.font
             * @expand font
             */
            font: Variables.font({
                color: globalVars.mainColors.fg,
            }),
        });

        return {
            mediaQueries,
            textContainer,
            title,
            description,
            userText,
            userName,
            userTitle,
            options,
            avatarContainer,
            avatarLink,
            avatar,
        };
    },
);
