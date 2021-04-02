/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { userSpotlightVariables } from "./UserSpotlight.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { url } from "csx";
import { DeepPartial } from "redux";
import { IUserSpotlightOptions } from "@library/userSpotlight/UserSpotlight.variables";

export const userSpotlightClasses = useThemeCache(
    (params?: { options?: DeepPartial<IUserSpotlightOptions>; shouldWrap?: boolean }) => {
        const { options, shouldWrap = false } = params ?? {};
        const vars = userSpotlightVariables(options);
        const globalVars = globalVariables();
        const mediaQueries = vars.mediaQueries();

        const root = css(
            {
                display: "flex",
                justifyContent: "space-around",
                alignItems: "center",
                minWidth: styleUnit(globalVars.foundationalWidths.panelWidth),
                ...Mixins.box(vars.options.box, { noPaddings: true }),
            },
            shouldWrap && {
                flexWrap: "wrap",
            },
        );

        const avatarContainer = css(
            {
                flex: "0 0 auto",
                backgroundImage: vars.avatarContainer.bgImage ? url(vars.avatarContainer.bgImage) : undefined,
                backgroundPosition: vars.avatarContainer.bgPosition,
                backgroundColor: ColorsUtils.colorOut(vars.avatarContainer.bg),
                width: vars.avatarContainer.sizing.width,
                height: vars.avatarContainer.sizing.height,
                ...Mixins.box(vars.options.box, { onlyPaddings: true }),
            },
            shouldWrap
                ? {
                      paddingBottom: 0,
                      ...Mixins.margin(vars.avatarContainer.marginWrapped),
                  }
                : {
                      paddingRight: 0,
                      ...Mixins.margin(vars.avatarContainer.margin),
                  },
            mediaQueries.mobile({
                width: vars.avatarContainer.sizingMobile.width,
                height: vars.avatarContainer.sizingMobile.height,
            }),
        );

        const avatarLink = css({
            display: vars.avatarLink.display,
            ...Mixins.margin(vars.avatarLink.margin),
        });

        const avatar = css(
            {
                ...Mixins.border(vars.avatar.border),
            },
            mediaQueries.mobile({
                width: vars.avatar.sizeMobile,
                height: vars.avatar.sizeMobile,
            }),
        );

        const boxSpacing = vars.options.box.spacing;
        const horizontalSpacer = boxSpacing.horizontal ?? boxSpacing.all ?? globalVars.gutter.size;
        const verticalSpacer = boxSpacing.vertical ?? boxSpacing.all ?? globalVars.gutter.size;

        const textContainer = css(
            {
                display: "flex",
                flexDirection: "column",
                flexBasis: 130,
                flexGrow: 1,
                ...Mixins.font(vars.textContainer.font),
                ...Mixins.box(vars.options.box, { onlyPaddings: true }),
            },
            shouldWrap
                ? {
                      paddingTop: verticalSpacer,
                  }
                : {
                      paddingLeft: horizontalSpacer,
                  },
            mediaQueries.mobile({
                ...Mixins.font(vars.textContainer.fontMobile),
            }),
        );

        const title = css(
            {
                ...Mixins.font(vars.title.font),
                ...Mixins.padding(vars.title.spacing),
            },
            mediaQueries.mobile({
                ...Mixins.font(vars.title.fontMobile),
            }),
        );

        const description = css({
            ...Mixins.font(vars.description.font),
            ...Mixins.padding(vars.description.spacing),
        });

        const userText = css(
            {
                ...Mixins.font(vars.userText.font),
                ...Mixins.padding(vars.userText.padding),
                ...Mixins.margin(vars.userText.margin),
                alignSelf: vars.options.userTextAlignment === "right" ? "flex-end" : undefined,
            },
            mediaQueries.mobile({
                ...Mixins.font(vars.userText.fontMobile),
            }),
        );

        const userName = css(
            {
                ...Mixins.font(vars.userName.font),
            },
            mediaQueries.mobile({
                ...Mixins.font(vars.userName.fontMobile),
            }),
        );

        const userTitle = css({
            ...Mixins.font(vars.userTitle.font),
        });

        return {
            root,
            avatarContainer,
            avatarLink,
            avatar,
            textContainer,
            title,
            description,
            userText,
            userName,
            userTitle,
        };
    },
);
