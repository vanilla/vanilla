/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, unit, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { important, percent } from "csx";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { profileVariables, ProfilePhotoAlignment } from "@dashboard/compatibilityStyles/pages/Profile.variables";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { metasVariables } from "@library/metas/Metas.variables";
import { injectGlobal } from "@emotion/css";

export const profileCompatCSS = () => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();
    const vars = profileVariables();

    MixinsFoundation.contentBoxes(vars.contentBoxes, "Profile");
    MixinsFoundation.contentBoxes(vars.contentBoxes, "ProfileEdit");
    MixinsFoundation.contentBoxes(vars.panelBoxes, "Profile", ".Panel");
    MixinsFoundation.contentBoxes(vars.panelBoxes, "ProfileEdit", ".Panel");

    cssOut(`body.Section-Profile .Gloss, body.Section-Profile .Profile-rank`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
    });

    cssOut(`body.Section-Profile .About a`, {
        display: "inline",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("large"),
        }),
    });

    cssOut(`body.Section-Profile .DataCounts`, {
        width: "100%",
    });

    cssOut(`body.Section-Profile .Profile dd, dt`, {
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("large"),
            size: `${unit(globalVars.fontSizeAndWeightVars("large").size)} !important`,
            lineHeight: `${(metasVars.font.lineHeight! as number) * 1.25} !important`,
        }),
    });

    cssOut(
        `body.Section-Profile .Profile dt`,
        {
            width: percent(20),
        },
        oneColumnVariables().mediaQueries().xs({
            width: "100%",
            display: "block",
            paddingLeft: 0,
        }),
    );

    cssOut(
        `body.Section-Profile .Profile dd`,
        {
            width: "calc(80% - 16px)",
            paddingLeft: 16,
        },
        oneColumnVariables().mediaQueries().oneColumnDown({
            width: "100%",
            display: "block",
            paddingLeft: 0,
        }),
    );

    cssOut(`.DataList.Activities a.CommentLink, .DataList.Activities a.CommentLink:hover`, {
        color: ColorsUtils.colorOut(metasVars.font.color, {
            makeImportant: true,
        }),
    });

    cssOut(`body.Section-Profile .PhotoWrapLarge`, {
        display: "block",
        width: vars.photo.size,
        height: vars.photo.size,
        borderRadius: vars.photo.border.radius,
        marginLeft: "auto",
        marginRight: "auto",
    });

    cssOut(`body.Section-Profile .PhotoWrapLarge img`, {
        width: percent(100),
        height: percent(100),
        objectFit: "cover",
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
        width: vars.photo.size,
    });

    cssOut(`.PhotoWrap a.ChangePicture .icon`, {
        display: "inline-block",
        verticalAlign: "middle",
        fontSize: styleUnit(20),
        color: ColorsUtils.colorOut(globalVars.elementaryColors.white),
        marginRight: styleUnit(10),
    });

    injectGlobal({
        ".Profile .Panel .UserBox a.Username": {
            color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        },
    });
};
