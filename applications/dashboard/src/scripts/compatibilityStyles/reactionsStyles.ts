/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { allLinkStates, negative } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";

export const reactionsCSS = () => {
    const vars = globalVariables();

    cssOut(`.Reactions`, {
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "flex-start",
        marginLeft: important(styleUnit(negative(vars.meta.spacing.default)) as string),
        width: calc(`100% + ${styleUnit(vars.meta.spacing.default * 2)}`),
        padding: styleUnit(vars.meta.spacing.default),
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
            fontSize: styleUnit(vars.meta.text.size),
            margin: styleUnit(vars.meta.spacing.default),
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
        color: ColorsUtils.colorOut(vars.meta.colors.fg),
        ...allLinkStates({
            // noState: {
            //     color: ColorsUtils.colorOut(vars.links.colors.default),
            // },
            hover: {
                color: ColorsUtils.colorOut(vars.links.colors.hover),
            },
            focus: {
                color: ColorsUtils.colorOut(vars.links.colors.focus),
            },
            active: {
                color: ColorsUtils.colorOut(vars.links.colors.active),
            },
        }),
    });
};
