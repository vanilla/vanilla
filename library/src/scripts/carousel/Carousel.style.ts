/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { percent } from "csx";
import { userSelect } from "@library/styles/styleHelpersFeedback";

export const carouselClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("carouselClasses");

    const sectionWrapper = style("sectionWrapper", {
        position: "relative",
    });

    const skipCarousel = style("skipCarousel", {
        position: "absolute",
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        border: 0,
        borderRadius: styleUnit(6),
        clip: "rect(0 0 0 0)",
        height: styleUnit(0),
        width: styleUnit(0),
        margin: styleUnit(-1),
        padding: 0,
        overflow: "hidden",
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        zIndex: 10,
        ...{
            "&:focus, &:active": {
                // This is over the icon and we want it to be a little further to the left of the main nav
                left: styleUnit(0),
                width: styleUnit(165),
                height: styleUnit(38),
                clip: "auto",
            },
        },
    });

    const carousel = style("carousel", {
        display: "flex",
        flexDirection: "row",
        position: "relative",
        button: {
            backgroundColor: "transparent",
            border: "none",
        },
        "& [data-direction]": {
            outline: "none",
            position: "absolute",
            top: 0,
            bottom: 0,
            height: "100%",
            svg: { position: "relative" },
        },
        "& [data-direction='prev']": {
            left: -36,
        },
        "& [data-direction='next']": {
            right: -36,
            zIndex: 1,
        },
        "& .focus-visible:not(button), & a:focus": {
            outlineWidth: "1px !important",
            outlineStyle: "solid !important",
            outlineColor: `${ColorsUtils.colorOut(globalVars.mainColors.primary)} !important`,
        },
        "& .focus-visible > svg": {
            borderWidth: 2,
            borderStyle: "solid",
            borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
        },
    });

    const sliderWrapper = style("sliderWrapper", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        position: "relative",
        zIndex: 0,
        overflow: "hidden",
        width: percent(100),
        "& *": {
            ...userSelect("none"),
        },
    });

    const slider = style("slider", {
        willChange: "transform",
        position: "absolute",
        right: "auto",
        display: "flex",
        flexDirection: "row",
        padding: "0 2px",
        ".swipable": {
            display: "flex",
            flexDirection: "row",
        },
        ".swipable > * + *": {
            marginLeft: 16,
        },
    });

    const pagingWrapper = style("pagingWrapper", {
        display: "flex",
        flexDirection: "row",
        justifyContent: "center",
        button: {
            backgroundColor: "transparent",
            border: "none",
            display: "flex",
            alignItems: "center",
        },

        marginTop: 8,
    });

    const dotWrapper = style("dotWrapper", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        listStyle: "none",
        paddingLeft: 0,
        "& .focus-visible": {
            outline: "none",
        },
    });

    const dotBt = style("dotBt", {
        padding: "0 4px",
        height: "24px !important",
        minWidth: "24px !important",
        width: "24px !important",

        "&.active > span": {
            backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.primary.fade(0.8)),
        },
        "&.focus-visible > span": {
            borderWidth: 2,
            borderStyle: "solid",
            borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
        },
        "&[disabled]": {
            opacity: "1 !important",
        },
    });

    const dot = style("dot", {
        width: 10,
        height: 10,
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.fg.fade(0.5)),
        borderRadius: percent(50),
        display: "inline-block",
    });

    return {
        sectionWrapper,
        skipCarousel,
        carousel,
        sliderWrapper,
        slider,
        pagingWrapper,
        dotWrapper,
        dotBt,
        dot,
    };
});
