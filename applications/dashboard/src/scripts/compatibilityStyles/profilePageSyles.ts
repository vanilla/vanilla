/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { important } from "csx";

export const profilePageCSS = () => {
    const globalVars = globalVariables();

    cssOut(`body.Section-Profile .Gloss, body.Section-Profile .Profile-rank`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
    });

    cssOut(`.DataList.Activities a.CommentLink, .DataList.Activities a.CommentLink:hover`, {
        color: ColorsUtils.colorOut(globalVars.meta.text.color, {
            makeImportant: true,
        }),
    });

    cssOut(`.PhotoWrap a.ChangePicture .ChangePicture-Text `, {
        ...absolutePosition.fullSizeOfParent(),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        background: ColorsUtils.colorOut(globalVars.elementaryColors.black.fade(0.4), {
            makeImportant: true,
        }),
        ...userSelect(),
    });
    cssOut(`.PhotoWrap a.ChangePicture:not(:hover) .ChangePicture-Text `, {
        ...Mixins.absolute.srOnly(),
    });

    cssOut(`.PhotoWrap a.ChangePicture`, {
        background: important("none"),
        textDecoration: "none",
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        display: important("flex"),
        alignItems: "center",
        justifyContent: "center",
        opacity: important(1), // for accessibility, we don't want to hide the link from the screen readers
    });

    cssOut(`body.Section-Profile .Panel .PhotoWrapLarge`, {
        position: "relative",
    });

    cssOut(`.PhotoWrap a.ChangePicture .icon`, {
        display: "inline-block",
        verticalAlign: "middle",
        fontSize: styleUnit(20),
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        marginRight: styleUnit(10),
    });
};
