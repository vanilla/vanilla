import { useThemeCache } from "@library/styles/styleUtils";
import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";

export const layoutSectionThumbnailsClasses = useThemeCache(() => {
    const container = css({
        display: "flex",
        flexDirection: "column",
    });
    const description = css({
        marginBottom: 20,
    });

    const gutterSize = 24;

    const thumbnails = css({
        display: "grid",
        gridTemplateColumns: "repeat(auto-fill, minmax(230px, 1fr))",
        ...extendItemContainer(gutterSize / 2),
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
    const labelContainer = css({
        display: "flex",
        alignItems: "center",
        marginLeft: 4,
        marginTop: 8,
    });
    const informationIcon = css({
        display: "flex",
        marginLeft: 16,
        [`&:hover,&active,&:focus,&.focus-visible`]: {
            color: ColorsUtils.colorOut(globalVariables().mainColors.primary),
        },
    });

    return {
        thumbnailImage,
        container,
        description,
        thumbnails,
        thumbnailWrapper,
        thumbnail,
        labelContainer,
        informationIcon,
    };
});
