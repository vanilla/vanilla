/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject, css } from "@emotion/css";
import { userSpotlightVariables } from "./UserSpotlight.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { px, url } from "csx";
import { DeepPartial } from "redux";
import { IUserSpotlightOptions } from "@library/userSpotlight/UserSpotlight.variables";

export const userSpotlightClasses = useThemeCache((optionsOverrides?: DeepPartial<IUserSpotlightOptions>) => {
    const vars = userSpotlightVariables(optionsOverrides);
    const globalVars = globalVariables();
    const mediaQueries = vars.mediaQueries();

    const root = css({
        display: "flex",
        alignItems: "center",
        minWidth: styleUnit(globalVars.foundationalWidths.panelWidth),
        ...Mixins.box(vars.options.box),
        ...Mixins.margin(vars.options.container.spacing),
    });

    const avatarContainer = css(
        {
            flex: "0 0 auto",
            backgroundImage: vars.avatarContainer.bgImage ? url(vars.avatarContainer.bgImage) : undefined,
            backgroundPosition: vars.avatarContainer.bgPosition,
            backgroundColor: ColorsUtils.colorOut(vars.avatarContainer.bg),
            width: vars.avatarContainer.sizing.width,
            height: vars.avatarContainer.sizing.height,
            ...Mixins.padding(vars.avatarContainer.padding),
            ...Mixins.margin(vars.avatarContainer.margin),
        },
        mediaQueries.mobile({
            width: vars.avatarContainer.sizingMobile.width,
            height: vars.avatarContainer.sizingMobile.height,
            ...Mixins.padding(vars.avatarContainer.paddingMobile),
            ...Mixins.margin(vars.avatarContainer.marginMobile),
        }),
    );

    const avatarLink = css({
        display: vars.avatarLink.display,
        ...Mixins.padding(vars.avatarLink.padding),
    });

    const avatar = css(
        {
            ...Mixins.border(vars.avatar.border),
            width: vars.avatar.sizing.width,
            height: vars.avatar.sizing.height,
        },
        mediaQueries.mobile({
            width: vars.avatar.sizingMobile.width,
            height: vars.avatar.sizingMobile.height,
        }),
    );

    const textContainer = css(
        {
            display: "flex",
            flexDirection: "column",
            ...Mixins.padding(vars.textContainer.spacing),
            ...Mixins.font(vars.textContainer.font),
        },
        mediaQueries.mobile({
            ...Mixins.padding(vars.textContainer.spacingMobile),
            ...Mixins.font(vars.textContainer.fontMobile),
        }),
    );

    const title = css(
        {
            ...Mixins.font(vars.title.font),
            ...Mixins.padding(vars.title.spacing),
        },
        mediaQueries.mobile({
            ...Mixins.padding(vars.title.spacingMobile),
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
            ...Mixins.padding(vars.userText.paddingMobile),
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
});
