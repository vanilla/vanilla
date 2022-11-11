/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useThemeCache } from "@library/styles/themeCache";
import { Mixins } from "@library/styles/Mixins";
import { callToActionVariables, ICallToActionOptions } from "@library/callToAction/CallToAction.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { DeepPartial } from "redux";
import { styleUnit } from "@library/styles/styleUnit";
import { percent } from "csx";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { css } from "@emotion/css";

export const callToActionClasses = useThemeCache((optionsOverrides?: DeepPartial<ICallToActionOptions>) => {
    const vars = callToActionVariables(optionsOverrides);
    const globalVars = globalVariables();

    const root = css({
        ...Mixins.box(vars.options.box, { noPaddings: true }),
        minWidth: styleUnit(vars.sizing.minWidth),
        display: "flex",
        flexDirection: "column",
        overflow: "hidden",

        //we are going to use <img> with src-sets in the component directly, so no background-image css
        backgroundImage: "none",
    });

    const isImageLeft = vars.options.imagePlacement === "left";
    const isImageTop = vars.options.imagePlacement === "top";
    const mediaQueries = vars.mediaQueries();
    const container = css(
        {
            position: "relative",
            flex: 1,
            display: undefined,
            alignItems: undefined,
            paddingTop: undefined,
            paddingLeft: undefined,
            ...(isImageLeft ? { display: "flex" } : {}),
        },
        mediaQueries.oneColumn({
            ...(isImageLeft ? { display: "block" } : {}),
        }),
    );
    const title = css({
        position: "relative",
        marginBottom: 0,
        ...Mixins.padding(vars.title.spacing),
        ...Mixins.font({ ...vars.title.font, color: optionsOverrides?.textColor }),
    });
    const absoluteTitle = css({
        ...Mixins.font(vars.title.font),
        marginBottom: 16,
    });
    const absoluteDescription = css({
        ...Mixins.font(vars.description.font),
        marginBottom: 16,
    });
    const description = css({
        position: "relative",
        ...Mixins.padding(vars.description.spacing),
        ...Mixins.font({ ...vars.description.font, color: optionsOverrides?.textColor }),
    });
    const link = css({
        ...Mixins.margin(vars.link.spacing),
    });

    const button = css({
        position: "relative",
    });

    const compactButton = css({
        "&&": {
            minWidth: 86,
        },
    });

    const linksWrapper = css({
        display: "inline-flex",
        flexWrap: "wrap",
        justifyContent: vars.options.alignment,
        ...Mixins.spaceChildrenEvenly(vars.link.spacing),
        alignItems: "center",
    });

    const imageAspectRatio = percent((vars.image.ratio.height / vars.image.ratio.width) * 100);

    const imageContainerWrapper = css({
        maxHeight: styleUnit(vars.image.maxHeight),
        overflow: "hidden",
    });

    const image = css({
        ...Mixins.absolute.fullSizeOfParent(),
        objectFit: "cover",
        objectPosition: "center center",
    });

    const absoluteFullParentSize = css({
        ...Mixins.absolute.fullSizeOfParent(),
    });

    const overlayColor = ColorsUtils.isLightColor(optionsOverrides?.textColor ?? globalVars.mainColors.fg)
        ? globalVars.elementaryColors.black.fade(0.25)
        : globalVars.elementaryColors.white.fade(0.25);

    const backgroundOverlay = css({
        background: ColorsUtils.colorOut(overlayColor),
    });

    const contentPaddings = Mixins.box(vars.options.box, { onlyPaddings: true });
    const content = css(
        {
            textAlign: vars.options.alignment,
            flex: isImageLeft ? 1 : undefined,
            ...contentPaddings,
        },
        mediaQueries.oneColumn({
            ...(isImageLeft ? { flex: undefined } : {}),
        }),
    );

    const imageContainer = css(
        {
            background: ColorsUtils.colorOut(globalVars.mixPrimaryAndBg(0.08)),
            width: percent(100),
            paddingTop: isImageTop ? imageAspectRatio : undefined,
            position: "relative",
            marginBottom: !contentPaddings.paddingTop && isImageTop ? 16 : undefined,
            marginRight: !contentPaddings.paddingRight && isImageLeft ? 16 : undefined,
        },
        mediaQueries.oneColumn({
            ...(isImageLeft
                ? {
                      marginBottom: !contentPaddings.paddingTop ? 16 : undefined,
                      marginRight: "none",
                      paddingTop: imageAspectRatio,
                  }
                : {}),
        }),
    );

    const imageWidthConstraint = useThemeCache((maxWidth: number) => {
        const imageLeftMaxWidth = isImageLeft && vars.image?.maxWidth ? vars.image?.maxWidth : undefined;
        const canApplyConstraint =
            isImageLeft && !imageLeftMaxWidth && maxWidth > 0 && maxWidth <= vars.sizing.minWidth;
        const defaultMaxWidth = isImageLeft ? vars.sizing.minWidth : undefined;
        const maxWidthWithBreakpoint = vars.image?.maxWidth ?? percent(100);
        return css(
            {
                maxWidth: canApplyConstraint ? maxWidth : defaultMaxWidth,
            },
            mediaQueries.oneColumn({
                ...(isImageLeft ? { maxWidth: maxWidthWithBreakpoint } : {}),
            }),
        );
    });

    return {
        root,
        container,
        title,
        absoluteTitle,
        description,
        absoluteDescription,
        content,
        imageContainer,
        imageContainerWrapper,
        image,
        imageWidthConstraint,
        link,
        linksWrapper,
        button,
        compactButton,
        absoluteFullParentSize,
        backgroundOverlay,
    };
});
