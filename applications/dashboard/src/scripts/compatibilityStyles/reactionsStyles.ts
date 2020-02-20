/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { allLinkStates, colorOut, negative, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export const reactionsCSS = () => {
    const vars = globalVariables();

    cssOut(`.Reactions`, {
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "flex-start",
        marginLeft: important(unit(negative(vars.meta.spacing.default)) as string),
        width: calc(`100% + ${unit(vars.meta.spacing.default * 2)}`),
        padding: unit(vars.meta.spacing.default),
    });

    cssOut(
        `
        .DataList .Reactions > .ReactButton,
        .MessageList .Reactions > .ReactButton,
        .Reactions  > .FlagMenu,
        .DataList .Reactions .ReactButton,
        .MessageList .Reactions .ReactButton
    `,
        {
            fontSize: unit(vars.meta.text.fontSize),
            margin: unit(vars.meta.spacing.default),
            textDecoration: "none",
        },
    );

    cssOut(".Item.Item .Reactions a, .Item:hover .Reactions a", {
        width: "initial",
    });

    cssOut(".FlagMenu > .ReactButton", {
        margin: important(0),
    });

    cssOut(`.Reactions .ReactButton`, {
        color: colorOut(vars.meta.colors.fg),
        ...allLinkStates({
            // noState: {
            //     color: colorOut(vars.links.colors.default),
            // },
            hover: {
                color: colorOut(vars.links.colors.hover),
            },
            focus: {
                color: colorOut(vars.links.colors.focus),
            },
            active: {
                color: colorOut(vars.links.colors.active),
            },
        }),
    });
};
