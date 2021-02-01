/**
 * BlockColumn compatibility styles.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { forumVariables } from "@library/forms/forumStyleVars";
import { absolutePosition } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
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
        minHeight: styleUnit(userPhotoSizing.medium),
    });
}
