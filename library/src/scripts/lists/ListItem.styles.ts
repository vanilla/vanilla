/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { listItemVariables } from "@library/lists/ListItem.variables";
import { metasVariables } from "@library/metas/Metas.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { extendItemContainer } from "@library/styles/styleHelpers";
import { getPixelNumber } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const listItemClasses = useThemeCache(
    (asTile?: boolean, hasImage?: boolean, hasCheckbox?: boolean, isMobileTile?: boolean) => {
        const globalVars = globalVariables();
        const vars = listItemVariables();
        const homeWidgetItemVars = homeWidgetItemVariables();
        const isTile = asTile || isMobileTile;

        const listInTab = css({
            ".tabContent &": {
                marginTop: 12,
            },
        });

        const item = css({
            flex: 1,
            display: "flex",
            alignItems: "flex-start",
            ...(isTile &&
                (hasImage || hasCheckbox) && {
                    flexDirection: "column",
                    alignItems: "stretch",
                    padding: 0,
                }),
            ...(asTile &&
                !isMobileTile &&
                !hasImage && {
                    ...Mixins.box(homeWidgetItemVars.options.box, { onlyPaddings: true }),
                }),
        });

        const itemCentered = css({
            alignItems: "center",
        });

        const checkboxContainer = css({
            marginRight: "8px",
            ...(isTile && {
                display: "flex",
                alignItems: "center",
            }),
        });

        const contentContainer = css({
            display: "flex",
            flexDirection: "column",
            justifyContent: "space-between",
            flex: 1,
            color: ColorsUtils.colorOut(globalVars.mainColors.fg),
            ...((hasImage || isTile) && {
                ...Mixins.padding({
                    horizontal: 8,
                    vertical: isTile ? 8 : 0,
                }),
            }),
            ...(isTile &&
                !hasImage && {
                    ...Mixins.padding({ vertical: 0 }),
                }),
            ...(asTile &&
                !isMobileTile &&
                !hasImage && {
                    height: "100%",
                }),
        });

        const actionsContainer = css({
            display: "flex",
            alignItems: "center",

            // Don't allow options to extend the height of the title container.
            // The height will be set by the title.
            maxHeight: 20,
        });

        const tileActions = css({
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            ...(hasImage && {
                ...Mixins.padding({
                    horizontal: 8,
                    vertical: 8,
                }),
            }),
            ...(isTile && {
                ...Mixins.margin({
                    bottom: globalVars.gutter.quarter,
                }),
            }),
        });

        const iconAndCheckbox = css({
            display: "flex",
        });

        const iconContainer = css({
            position: "relative",
            display: "flex",
            alignItems: "center",
            ...(!hasImage && {
                marginRight: isTile ? 8 : 16,
                marginInlineEnd: isTile ? 8 : 16,
            }),
            ...(!isTile &&
                hasImage && {
                    position: "absolute",
                    width: "fit-content",
                    top: -10,
                    bottom: -10,
                    right: 0,
                    flexDirection: "column",
                    alignItems: "center",
                    justifyContent: "space-between",
                }),
        });

        const icon = css({
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            ...(!isTile &&
                hasImage && {
                    order: 1,
                }),
        });

        const secondIcon = css({
            ...Mixins.margin({ left: -10 }),
            ...(!isTile && {
                position: "initial",
                left: "initial",
                bottom: "initial",
                margin: 0,
            }),
            ...(!hasImage && {
                position: "absolute",
                top: 30,
                left: -5,
                margin: 0,
            }),
            ...(isTile &&
                hasCheckbox && {
                    position: "initial",
                    margin: -10,
                }),
        });

        const secondIconInList = css({});

        const titleContainer = css({
            display: "flex",
            alignItems: "flex-start",
        });

        const title = css({
            flex: 1,
            ...Mixins.font(vars.title.font),
            margin: 0,
            marginBottom: 0,
        });

        const titleLink = css(mixinListItemTitleLink());

        const mediaWrapSpacing = Mixins.margin({
            horizontal: 8,
        });

        const mobileMediaContainer = css({});

        const mediaWrapContainer = css({
            ...(isTile
                ? {
                      aspectRation: "9/6",
                  }
                : {
                      width: 160,
                      ...Mixins.padding({ right: 16 }),
                  }),
            position: "relative",
            "& > div:first-child": {
                borderRadius: isTile ? 0 : 8,
                overflow: "hidden",
                position: "relative",
            },
        });

        const mediaContainer = css({
            borderRadius: isTile ? 0 : 8,
            overflow: "hidden",
            position: "relative",
        });

        const mediaIconContainer = css({});

        const metasContainer = css({
            ...(isTile && {
                marginTop: "auto",
            }),
        });

        const description = css({
            ...Mixins.font(vars.description.font),
            marginBottom: 0,
            [`.${metasContainer} + &`]: {
                marginTop: 4,
            },
            [`& + .${metasContainer}`]: {
                marginTop: 8,
                ...(isTile && {
                    marginTop: "auto",
                }),
            },
            ...(isTile && {
                marginBottom: 8,
                lineHeight: "21px",
            }),
        });

        const metaWrapContainer = css({
            display: "flex",
            flexWrap: "wrap",
            flexDirection: "row-reverse",
            ...extendItemContainer(8),
            ...(isTile && {
                flexGrow: 1,
            }),
        });

        const metaDescriptionContainer = css({
            ...mediaWrapSpacing,
            flexGrow: 1,
            flexBasis: "300px",
            marginTop: 4,
            ...(isTile && {
                display: "flex",
                flexDirection: "column",
            }),
        });

        // Some delicate math required to vertically align the user icon and the first row of metas.
        const metasVars = metasVariables();
        const metaItemHeight = Math.round(metasVars.height + getPixelNumber(metasVars.spacing.vertical) * 2);

        const inlineIconContainer = css({
            ...Mixins.margin({
                right: getPixelNumber(metasVars.spacing.horizontal) * 2,
            }),
            [`& > *:first-child`]: {
                position: "relative",
                ...Mixins.margin({
                    top: `calc((${metaItemHeight}px - 100%)/2)`,
                    right: 0,
                }),
            },
        });

        const inlineIconAndMetasContainer = css({
            display: "flex",
            alignItems: "center",
            ...Mixins.margin({
                top: 18,
            }),
        });

        const twoIconsInMetas = css({
            [`& > *:first-child`]: {
                ...Mixins.padding({ right: 20 }),
                [`& > *:last-child`]: {
                    left: 35,
                    top: 10,
                },
            },
        });

        return {
            listInTab,
            item,
            itemCentered,
            checkboxContainer,
            contentContainer,
            actionsContainer,
            tileActions,
            iconAndCheckbox,
            iconContainer,
            icon,
            secondIcon,
            secondIconInList,
            titleContainer,
            title,
            titleLink,
            mobileMediaContainer,
            mediaWrapContainer,
            mediaContainer,
            mediaIconContainer,
            description,
            metasContainer,
            metaWrapContainer,
            metaDescriptionContainer,
            inlineIconContainer,
            inlineIconAndMetasContainer,
            twoIconsInMetas,
        };
    },
);

export function mixinListItemTitleLink() {
    const vars = listItemVariables();
    return {
        ...Mixins.font(vars.title.font),
        "&:hover, &:focus, &:active": {
            ...Mixins.font(vars.title.fontState),
        },
    };
}
