/**
 * BlockColumn compatibility styles.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { styleUnit } from "@library/styles/styleUnit";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";

export function blockColumnCSS() {
    const photoVars = userPhotoVariables();

    // Reworked placement of BlockColumn, because they were misaligned and also causing false positives on the accessibility tests.
    cssOut(`.BlockColumn .Block.Wrap`, {
        display: "flex",
        flexWrap: "wrap",
        flexDirection: "column",
        justifyContent: "space-between",
        minHeight: styleUnit(photoVars.sizing.medium),
    });
}
