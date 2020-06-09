/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, importantColorOut, srOnly } from "@library/styles/styleHelpers";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { important } from "csx";

export const profilePageCSS = () => {
    const globalVars = globalVariables();

    cssOut(`body.Section-Profile .Gloss, body.Section-Profile .Profile-rank`, {
        color: colorOut(globalVars.mainColors.primary),
        borderColor: colorOut(globalVars.mainColors.primary),
    });

    cssOut(`.DataList.Activities a.CommentLink, .DataList.Activities a.CommentLink:hover`, {
        color: importantColorOut(globalVars.elementaryColors.lowContrast),
    });

    cssOut(`.PhotoWrap a.ChangePicture`, {
        textDecoration: "none",
    });

    cssOut(`.PhotoWrap a.ChangePicture:not(:hover) .ChangePicture-Text `, {
        ...srOnly(), // still visible to screen readers
    });

    cssOut(`.PhotoWrap a.ChangePicture`, {
        backgroundColor: importantColorOut(globalVars.elementaryColors.black.fade(0.4)),
        color: colorOut(globalVars.elementaryColors.white),
        display: important("flex"),
        alignItems: "center",
        justifyContent: "center",
        opacity: important(1), // for accessibility, we don't want to hide the link from the screen readers
    });

    cssOut(`body.Section-Profile .Panel .PhotoWrapLarge`, {
        position: "relative",
    });
};
