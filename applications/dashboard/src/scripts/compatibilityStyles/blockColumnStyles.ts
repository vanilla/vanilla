/**
 * BlockColumn compatibility styles.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { forumVariables } from "@library/forms/forumStyleVars";
import { absolutePosition, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, percent } from "csx";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";

export function blockColumnCSS() {
    const globalVars = globalVariables();
    const forumVars = forumVariables();
    const layoutVars = forumLayoutVariables();
    const userPhotoSizing = forumVars.userPhoto.sizing;

    // Reworked placement of BlockColumn, because they were misaligned and also causing false positives on the accessibility tests.
    cssOut(`.BlockColumn .Block.Wrap`, {
        display: "flex",
        flexWrap: "wrap",
        flexDirection: "column",
        justifyContent: "space-between",
        minHeight: unit(userPhotoSizing.medium),
    });

    const paddingTop: number = parseInt(`${layoutVars.cell.paddings.vertical}`) ?? 0;
    const paddingLeft: number = parseInt(`${layoutVars.cell.paddings.horizontal}`) ?? 0;

    // With photo or only an idea
    cssOut(
        `
        .BlockColumn .Block.Wrap .userCardWrapper-photo.userCardWrapper-photo,
        .BlockColumn .Block.Wrap .idea-counter-module.idea-counter-module
        `,
        {
            width: unit(userPhotoSizing.medium),
            height: unit(userPhotoSizing.medium),
            ...absolutePosition.topLeft(paddingTop, calc(`${unit(paddingLeft)} / 2`)),
        },
    );
}
