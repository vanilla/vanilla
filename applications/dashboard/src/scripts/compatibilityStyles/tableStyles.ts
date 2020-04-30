/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, ColorValues, importantUnit, paddings, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { important } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";

export const tableCSS = () => {
    const vars = globalVariables();

    cssOut(
        `
        .DataTable thead td,
        .DataTableWrap.GroupWrap thead td,
    `,
        {
            ...paddings({
                vertical: vars.gutter.size,
                horizontal: importantUnit(vars.gutter.half),
            }),
        },
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
            color: colorOut(vars.mainColors.fg),
            fontSize: unit(vars.meta.text.fontSize),
            textDecoration: important("none"),
        },
    );

    const linkColors = vars.links.colors;
    cssOut(
        `.Container .DataTable .DiscussionName > .Wrap > a.MItem.Category`,
        clickableItemStates(
            {},
            {
                default: linkColors.default ? { borderColor: colorOut(linkColors.default) } : undefined,
                hover: linkColors.hover ? { borderColor: colorOut(linkColors.hover) } : undefined,
                focus: linkColors.focus ? { borderColor: colorOut(linkColors.focus) } : undefined,
                keyboardFocus: linkColors.keyboardFocus
                    ? { borderColor: colorOut(linkColors.keyboardFocus) }
                    : undefined,
                active: linkColors.active ? { borderColor: colorOut(linkColors.active) } : undefined,
                visited: linkColors.visited ? { borderColor: colorOut(linkColors.visited) } : undefined,
            },
        ),
    );
};
