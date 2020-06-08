/**
 * BlockColumn compatibility styles.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { forumVariables } from "@library/forms/forumStyleVars";
import { userPhotoMixins } from "@library/headers/mebox/pieces/userPhotoStyles";
import { absolutePosition, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc } from "csx";

export function blockColumnCSS() {
    const globalVars = globalVariables();
    const forumVars = forumVariables();
    const userPhotoSizing = forumVars.userPhoto.sizing;
    const mixins = userPhotoMixins(forumVars.userPhoto);
    // Reworked placement of BlockColumn, because they were misaligned and also causing false positives on the accessibility tests.
    cssOut(`.BlockColumn .Block.Wrap`, {
        display: "flex",
        flexWrap: "wrap",
        flexDirection: "column",
        overflow: "hidden",
        justifyContent: "space-between",
        alignItems: "flex-end",
        minHeight: unit(userPhotoSizing.medium),
    });

    cssOut(
        `
        .Groups .DataTable tbody td.LatestPost .Wrap,
        .Groups .DataTable tbody td.LastUser .Wrap,
        .Groups .DataTable tbody td.FirstUser .Wrap,
        .DataTable tbody td.LatestPost .Wrap,
        .DataTable tbody td.LastUser .Wrap,
        .DataTable tbody td.FirstUser .Wrap
    `,
        {
            paddingLeft: 0,
        },
    );

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
            width: calc(`100% - ${unit(userPhotoSizing.medium + 12)}`),
            margin: 0,
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
            ...absolutePosition.topLeft(),
            width: unit(userPhotoSizing.medium),
            height: unit(userPhotoSizing.medium),
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
        {
            ...mixins.photo,
        },
    );
}
