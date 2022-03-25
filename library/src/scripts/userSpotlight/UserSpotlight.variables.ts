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
import { IThemeVariables } from "@library/theming/themeReducer";
import { BorderType } from "@library/styles/styleHelpers";
import { UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { media } from "@library/styles/styleShim";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";

export interface IUserSpotlightOptions extends IHomeWidgetContainerOptions {
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
        const options = makeThemeVars(
            "options",
            {
                /**
                 * @varGroup userSpotlight.options.box
                 * @title User Spotlight - Box
                 * @expand box
                 */
                box: Variables.box({
                    background: optionOverrides?.innerBackground,
                    borderType: optionOverrides?.borderType as BorderType,
                    border: globalVars.border,
                }),

                /**
                 * @varGroup userSpotlight.options.container
                 * @title User Spotlight - Container
                 */
                container: {
                    /**
                     * @var userSpotlight.options.container.spacing
                     * @expand spacing
                     */
                    spacing: Variables.spacing({
                        bottom: 48,
                    }),

                    /**
                     * @var userSpotlight.options.container.spacingMobile
                     * @expand spacingMobile
                     */
                    spacingMobile: Variables.spacing({}),
                },

                /**
                 * @var userSpotlight.options.userTextAlignment
                 * @description Whether user name and user title aligned left or right.
                 */
                userTextAlignment: optionOverrides?.userTextAlignment
                    ? optionOverrides.userTextAlignment
                    : ("left" as "left" | "right"),
            },
            optionOverrides,
        );

        /**
         * @varGroup userSpotlight.breakPoints
         */
        const breakPoints = makeThemeVars("breakPoints", {
            /**
             * @var userSpotlight.breakPoints.mobile
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

        /**
         * @varGroup userSpotlight.avatarContainer
         * @title Avatar Container
         */
        const avatarContainer = makeThemeVars("avatarContainer", {
            /**
             * @varGroup userSpotlight.avatarContainer.sizing
             * @title Sizing
             * @description Width and height of avatar container, normally used when there is a background image for avatar.
             */
            sizing: {
                /**
                 * @var userSpotlight.avatarContainer.sizing.width
                 * @type number | string
                 */
                width: undefined as number | string | undefined,
                /**
                 * @var userSpotlight.avatarContainer.sizing.height
                 * @type number | string
                 */
                height: undefined as number | string | undefined,
            },

            /**
             * @varGroup userSpotlight.avatarContainer.sizingMobile
             * @title Sizing - Mobile
             */
            sizingMobile: {
                /**
                 * @var userSpotlight.avatarContainer.sizingMobile.width
                 * @type number | string
                 */
                width: undefined as number | string | undefined,
                /**
                 * @var userSpotlight.avatarContainer.sizingMobile.height
                 * @type number | string
                 */
                height: undefined as number | string | undefined,
            },

            /**
             * @varGroup userSpotlight.avatarContainer.margin
             * @expand spacing
             */
            margin: Variables.spacing({}),

            /**
             * @varGroup userSpotlight.avatarContainer.marginWrapped
             * @expand spacing
             */
            marginWrapped: Variables.spacing({}),

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
             * @varGroup userSpotlight.avatarLink.margin
             * @expand spacing
             */
            margin: Variables.spacing({}),

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
             * @varGroup userSpotlight.avatar.border
             * @expand border
             */
            border: Variables.border({
                radius: 120,
            }),

            /**
             * @var userSpotlight.avatar.size
             * @title Avatar Size
             * @type string
             * @enum small | medium | large | xlarge
             */

            size: UserPhotoSize.LARGE,

            /**
             * @var userSpotlight.avatar.sizeMobile
             * @title Avatar Size Mobile
             * @description In case we want to make it smaller on mobile views.
             * @type number | string
             */
            sizeMobile: undefined as number | string | undefined,
        });

        /**
         * @varGroup userSpotlight.textContainer
         */
        const textContainer = makeThemeVars("textContainer", {
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
                ...globalVars.fontSizeAndWeightVars("subTitle", "bold"),
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
