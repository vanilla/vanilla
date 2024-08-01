/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { importantUnit, singleBorder } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important, percent } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { userPhotoMixins, userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { Mixins } from "@library/styles/Mixins";
import { metasVariables } from "@library/metas/Metas.variables";

export const tableCSS = () => {
    const vars = globalVariables();
    const metasVars = metasVariables();
    const layoutVars = forumLayoutVariables();
    const userPhotoSizing = userPhotoVariables().sizing;
    const mixins = userPhotoMixins();
    const margin = 12;

    cssOut(
        `
        .Groups .DataTable .LatestPostTitle,
        .Groups .DataTable .UserLink.BlockTitle,
        .Groups .DataTable .BigCount .Meta,
        .Groups .DataTable .Block.Wrap .Meta,
        .DataTable .LatestPostTitle,
        .DataTable .UserLink.BlockTitle,
        .DataTable .BigCount .Meta,
        .DataTable .Block.Wrap .Meta
        `,
        {
            width: calc(`100% - ${styleUnit(userPhotoSizing.medium + margin)}`),
            marginTop: 0,
        },
    );

    cssOut(
        `
        .Groups .DataTable .UserLink.BlockTitle,
        .DataTable .UserLink.BlockTitle,
        `,
        {
            fontWeight: vars.fonts.weights.normal,
        },
    );

    cssOut(
        `
        .Groups .DataTable tbody td.LatestPost .PhotoWrap,
        .Groups .DataTable tbody td.LastUser .PhotoWrap,
        .Groups .DataTable tbody td.FirstUser .PhotoWrap,
        .DataTable tbody td.LatestPost .PhotoWrap,
        .DataTable tbody td.LastUser .PhotoWrap,
        .DataTable tbody td.FirstUser .PhotoWrap
    `,
        {
            ...mixins.root,
            ...Mixins.absolute.topLeft(),
            top: 12,
            left: 6,
            width: styleUnit(userPhotoSizing.medium),
            height: styleUnit(userPhotoSizing.medium),
        },
    );

    cssOut(
        `
        .Groups .DataTable tbody td.LatestPost .PhotoWrap img,
        .Groups .DataTable tbody td.LastUser .PhotoWrap img,
        .Groups .DataTable tbody td.FirstUser .PhotoWrap img,
        .DataTable tbody td.LatestPost .PhotoWrap img,
        .DataTable tbody td.LastUser .PhotoWrap img,
        .DataTable tbody td.FirstUser .PhotoWrap img
    `,
        mixins.photo,
    );

    cssOut(
        `
        .Groups .DataTable tbody td.LatestPost a,
        .Groups .DataTable tbody td.LastUser a,
        .Groups .DataTable tbody td.FirstUser a,
        .DataTable tbody td.LatestPost a,
        .DataTable tbody td.LastUser a,
        .DataTable tbody td.FirstUser a
        `,
        {
            color: ColorsUtils.colorOut(vars.mainColors.fg),
            fontSize: styleUnit(metasVars.font.size),
            textDecoration: important("none"),
        },
    );

    cssOut(`.Groups .DataTable .Item td, .DataTable .Item td`, {
        borderBottom: singleBorder(),
        padding: 0,
    });

    cssOut(`.Groups .DataTable .Item:first-child td, .DataTable .Item:first-child td`, {
        borderTop: singleBorder(),
    });

    cssOut(`.Groups .DataTable td .Wrap, .DataTable td .Wrap`, {
        ...Mixins.padding({
            vertical: layoutVars.cell.paddings.vertical,
            left: calc(`${styleUnit(layoutVars.cell.paddings.horizontal)} / 2`),
            right: calc(`${styleUnit(layoutVars.cell.paddings.horizontal)} / 2`),
        }),
    });

    cssOut(
        `.Groups .DataTable .Excerpt, .Groups .DataTable .CategoryDescription, .DataTable .Excerpt, .DataTable .CategoryDescription`,
        {
            ...Mixins.font({
                ...vars.fontSizeAndWeightVars("medium"),
                color: ColorsUtils.colorOut(vars.mainColors.fg),
            }),
        },
    );

    cssOut(`.DataTable .DiscussionName .Title`, {
        width: calc(`100% - ${styleUnit(vars.icon.sizes.default * 2 + vars.gutter.quarter)}`),
    });
};
