/**
 * BlockColumn compatibility styles.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { cssOut } from "@dashboard/compatibilityStyles/index";

export function blockColumnCSS() {
    // Reworked placement of BlockColumn, because they were misaligned and also causing false positives on the accessibility tests.
    cssOut(`.BlockColumn .Block.Wrap`, {
        display: "flex",
        flexWrap: "wrap",
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
            position: "relative",
            top: "auto",
            left: "auto",
        },
    );
}
