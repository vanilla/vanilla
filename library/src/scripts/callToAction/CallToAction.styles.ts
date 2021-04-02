/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useThemeCache } from "@library/styles/themeCache";
import { styleFactory } from "@library/styles/styleUtils";
import { Mixins } from "@library/styles/Mixins";
import { callToActionVariables, ICallToActionOptions } from "@library/callToAction/CallToAction.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { DeepPartial } from "redux";
import { styleUnit } from "@library/styles/styleUnit";
import { percent } from "csx";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { extendItemContainer, negativeUnit } from "@library/styles/styleHelpers";

export const callToActionClasses = useThemeCache((optionsOverrides?: DeepPartial<ICallToActionOptions>) => {
    const vars = callToActionVariables(optionsOverrides);
    const style = styleFactory("cta-widget");
    const globalVars = globalVariables();

    const root = style({
        ...Mixins.box(vars.options.box, { noPaddings: true }),
        minWidth: styleUnit(vars.sizing.minWidth),
        display: "flex",
        flexDirection: "column",
        overflow: "hidden",
    });
    const isImageLeft = vars.options.imagePlacement === "left";
    const isImageTop = vars.options.imagePlacement === "top";
    const mediaQueries = vars.mediaQueries();
    const container = style(
        "container",
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
    const title = style("title", {
        ...Mixins.padding(vars.title.spacing),
        ...Mixins.font(vars.title.font),
    });
    const absoluteTitle = style("absoluteTitle", {
        ...Mixins.font(vars.title.font),
        marginBottom: 16,
    });
    const absoluteDescription = style("absoluteDescription", {
        ...Mixins.font(vars.description.font),
        marginBottom: 16,
    });
    const description = style("description", {
        ...Mixins.padding(vars.description.spacing),
        ...Mixins.font(vars.description.font),
    });
    const link = style("link", {
        ...Mixins.margin(vars.link.spacing),
    });

    const linksWrapper = style("linksWrapper", {
        display: "inline-flex",
        flexWrap: "wrap",
        justifyContent: vars.options.alignment,
        ...Mixins.spaceChildrenEvenly(vars.link.spacing),
    });

    const imageAspectRatio = percent((vars.image.ratio.height / vars.image.ratio.width) * 100);

    const imageContainerWrapper = style("imageContainerWrapper", {
        maxHeight: styleUnit(vars.image.maxHeight),
        overflow: "hidden",
    });

    const image = style("image", {
        ...Mixins.absolute.fullSizeOfParent(),
        objectFit: "cover",
        objectPosition: "center center",
    });

    const contentPaddings = Mixins.box(vars.options.box, { onlyPaddings: true });
    const content = style(
        "content",
        {
            textAlign: vars.options.alignment,
            flex: isImageLeft ? 1 : undefined,
            ...contentPaddings,
        },
        mediaQueries.oneColumn({
            ...(isImageLeft ? { flex: undefined } : {}),
        }),
    );

    const imageContainer = style(
        "imageContainer",
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
        return style(
            "imageWidthConstraint",
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
    };
});
