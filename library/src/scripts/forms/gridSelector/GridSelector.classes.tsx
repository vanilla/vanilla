/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { GridSelectorLayout } from "@library/forms/gridSelector/GridSelector";

export const gridSelectorClasses = useThemeCache((layout: GridSelectorLayout) => {
    const gridLayout = css({
        marginTop: 16,
        marginBottom: 16,
        width: "100%",
        height: "100%",
        display: "grid",
        gridTemplateColumns: `repeat(${layout.colCount}, 1fr)`,
        gridTemplateRows: `repeat(${layout.rowCount}, 1fr)`,
        gap: 8,
    });

    const highlighted = { background: ColorsUtils.colorOut(globalVariables().mainColors.primary.fade(0.1)) };

    const gridItem = css({
        display: "flex",
        aspectRatio: "1 / 1",
        border: `1px solid ${globalVariables().border.color}`,
        borderRadius: 4,
        cursor: "pointer",
        "&:hover, &:focus, &.hover, &:focus-visible, &.focus-visible": highlighted,
    });

    const nonSelectable = css({
        cursor: "initial",
    });

    const gridItemHighlighted = css(highlighted);

    return {
        gridLayout,
        gridItem,
        gridItemHighlighted,
        nonSelectable,
    };
});
