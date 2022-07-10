/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useThemeCache } from "@library/styles/styleUtils";
import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";

export const layoutThumbnailsClasses = useThemeCache(() => {
    const container = css({
        display: "flex",
        flexDirection: "column",
    });
    const description = css({
        marginBottom: 20,
        "&& a": {
            [`&:hover, &:active, &:focus, &.focus-visible`]: {
                textDecoration: "none",
            },
        },
    });

    const gutterSize = 24;

    const thumbnails = css({
        display: "grid",
        gridTemplateColumns: "repeat(auto-fill, minmax(230px, 1fr))",
        gridTemplateRows: "max-content",
        ...extendItemContainer(gutterSize / 2),
        minHeight: 460,
    });

    const smallerThumbnails = css({
        gridTemplateColumns: "repeat(auto-fill, minmax(130px, 1fr))",
    });

    const thumbnailWrapper = css({
        flex: 1,
        marginBottom: gutterSize,
        marginLeft: gutterSize / 2,
        marginRight: gutterSize / 2,
        cursor: "pointer",
    });
    const thumbnail = css({
        flex: 1,
        display: "block",
        ...Mixins.border({
            color: "#dddee0",
            width: 1,
            style: "solid",
            radius: 4,
        }),
        boxShadow: `0px 1px 3px 0px ${ColorsUtils.colorOut(globalVariables().mainColors.primary.fade(0.1))}`,
        [`&:hover, &:active, &:focus, &.focus-visible, &.isSelected`]: {
            background: ColorsUtils.colorOut(globalVariables().mainColors.primary.fade(0.1)),
            ...Mixins.border({
                color: ColorsUtils.colorOut(globalVariables().mainColors.primary),
                radius: 4,
            }),
        },
        "&.focus-visible": {
            boxShadow: `0 0 0 1px ${ColorsUtils.colorOut(globalVariables().mainColors.primary)}`,
        },
    });

    const thumbnailImage = css({
        width: "100%",
        minWidth: 200,
    });

    const thumbnailImageSmall = css({
        width: "100%",
        display: "block",
    });

    const labelContainer = css({
        display: "flex",
        alignItems: "center",
        marginLeft: 4,
        marginTop: 8,
        ...Mixins.font({ weight: 400 }),
    });

    const informationIcon = css({
        display: "flex",
        marginLeft: 16,
        [`&:hover,&active,&:focus,&.focus-visible`]: {
            color: ColorsUtils.colorOut(globalVariables().mainColors.primary),
        },
    });

    const searchContent = css({
        marginBottom: 16,
    });

    const searchLabel = css({
        width: "100%",
        ...Mixins.font({ weight: 400 }),
    });

    const searchInput = css({
        width: "100%",
        outline: 0,
        ...Mixins.padding({ vertical: 8, horizontal: 40 }),
        ...Mixins.border({
            color: globalVariables().border.color,
            radius: 6,
        }),
        [`&:hover, &:active, &:focus, &.focus-visible, &.isSelected`]: {
            ...Mixins.border({
                color: ColorsUtils.colorOut(globalVariables().mainColors.primary),
            }),
        },
    });

    const clearButton = css({
        position: "absolute",
        right: 8,
    });

    const form = css({
        display: "flex",
        flexDirection: "column",
        minHeight: 0,
    });

    return {
        form,
        thumbnailImage,
        thumbnailImageSmall,
        container,
        description,
        thumbnails,
        smallerThumbnails,
        thumbnailWrapper,
        thumbnail,
        labelContainer,
        informationIcon,
        searchContent,
        searchLabel,
        searchInput,
        clearButton,
    };
});
