/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { listItemVariables } from "@library/lists/ListItem.variables";
import { metasVariables } from "@library/metas/Metas.variables";
import { tagVariables } from "@library/metas/Tag.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { extendItemContainer } from "@library/styles/styleHelpers";
import { getPixelNumber } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { forceInt } from "@vanilla/utils";

export const listItemClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = listItemVariables();

    const item = css({
        display: "flex",
        alignItems: "flex-start",
    });
    const contentContainer = css({
        width: "100%",
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });

    const mediaWrapSpacing = Mixins.margin({
        horizontal: 8,
    });
    const mediaWrapContainer = css({
        display: "flex",
        flexWrap: "wrap",
        flexDirection: "row-reverse",
        ...extendItemContainer(8),
    });
    const metaDescriptionContainer = css({
        ...mediaWrapSpacing,
        flexGrow: 1,
        flexBasis: "300px",
        marginTop: 4,
    });
    const mobileMediaContainer = css({
        marginTop: 8,
        marginBottom: 8,
    });

    const mediaContainer = css({
        ...mediaWrapSpacing,
        flexGrow: 1,
        flexBasis: 160,
        marginTop: 8,
        maxWidth: 160,
        alignSelf: "flex-end",
    });

    const iconContainer = css({
        marginRight: globalVars.spacer.componentInner,
    });

    const metasContainer = css({});

    // Some delicate math required to vertically align the user icon and the first row of metas.
    const metasVars = metasVariables();
    const tagVars = tagVariables();
    const metaItemHeightIncludingMargins = Math.round(
        getPixelNumber(metasVars.font.size) * forceInt(metasVars.font?.lineHeight, 1) +
            getPixelNumber(metasVars.spacing.vertical) * 2 +
            getPixelNumber(tagVars.border.width) * 2,
    );

    const inlineIconContainer = css({
        ...Mixins.margin({
            right: getPixelNumber(metasVars.spacing.horizontal) * 2,
        }),
        [`& > *:first-child`]: {
            position: "relative",
            ...Mixins.margin({
                top: `calc((${metaItemHeightIncludingMargins}px - 100%)/2)`,
            }),
        },
    });

    const inlineIconAndMetasContainer = css({
        display: "flex",
        alignItems: "start",
        ...Mixins.margin({
            top: 18,
        }),
    });

    const title = css({
        flex: 1,
        ...Mixins.font(vars.title.font),
    });

    const titleContainer = css({
        display: "flex",
        alignItems: "flex-start",
    });
    const titleLink = css(mixinListItemTitleLink());
    const description = css({
        [`.${metasContainer} + &`]: {
            marginTop: 4,
        },
        [`& + .${metasContainer}`]: {
            marginTop: 8,
        },
    });
    const actionsContainer = css({
        display: "flex",
        alignItems: "center",

        // Don't allow options to extend the height of the title container.
        // The height will be set by the title.
        maxHeight: 20,
    });

    return {
        item,
        contentContainer,
        iconContainer,
        inlineIconAndMetasContainer,
        inlineIconContainer,
        metasContainer,
        mediaContainer,
        mobileMediaContainer,
        mediaWrapContainer,
        title,
        titleContainer,
        titleLink,
        description,
        actionsContainer,
        metaDescriptionContainer,
    };
});

export function mixinListItemTitleLink() {
    const vars = listItemVariables();
    return {
        ...Mixins.font(vars.title.font),
        "&:hover, &:focus, &:active": {
            ...Mixins.font(vars.title.fontState),
        },
    };
}
