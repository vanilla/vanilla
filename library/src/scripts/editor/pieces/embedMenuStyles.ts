/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { importantUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { percent } from "csx";
import { metasVariables } from "@library/metas/Metas.variables";
import { css } from "@emotion/css";

export const embedMenuClasses = useThemeCache(() => {
    const style = styleFactory("embedMenu");
    const metasVars = metasVariables();
    const globalVars = globalVariables();

    const root = css({
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        position: "absolute",
        top: 0,
        left: percent(50),
        ...Mixins.margin({ horizontal: "auto" }),
        transform: "translate(-50%, -50%)",
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
        ...Mixins.border(),
        ...shadowOrBorderBasedOnLightness(undefined, undefined, shadowHelper().toolbar()),
        zIndex: 100,
        ...Mixins.padding({
            vertical: 4,
            horizontal: 2,
        }),
        "&.isOpened": {
            borderBottomLeftRadius: 0,
            borderBottomRightRadius: 0,
        },
        "& > *": {
            ...Mixins.margin({ horizontal: 4 }),
        },
    });

    const form = style("form", {
        display: "block",
        width: percent(100),
    });

    const imageContainer = style("imageContainer", {
        position: "relative",
    });

    // Extra specific and defensive here because it's within userContent styles.
    const paragraph = style("paragraph", {
        ...{
            "&&": {
                // Specificity required to override default label styles
                ...Mixins.padding({
                    all: 0,
                    top: importantUnit(globalVars.gutter.quarter),
                }),
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium", "normal"),
                    lineHeight: globalVars.lineHeights.base,
                    color: metasVars.font.color,
                    align: "left",
                }),
            },
        },
    });
    const verticalPadding = style("verticalPadding", {
        ...Mixins.padding({
            vertical: styleUnit(globalVars.gutter.half),
        }),
    });

    return {
        root,
        form,
        imageContainer,
        paragraph,
        verticalPadding,
    };
});
