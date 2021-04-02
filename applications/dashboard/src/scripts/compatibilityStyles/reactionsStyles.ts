/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { allLinkStates, importantUnit, negative } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, important } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { metasVariables } from "@library/metas/Metas.variables";
import { Mixins } from "@library/styles/Mixins";

export const reactionsCSS = () => {
    const vars = globalVariables();
    const metasVars = metasVariables();

    cssOut(`.Reactions`, {
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "flex-start",
        marginLeft: importantUnit(negative(metasVars.spacing.horizontal)),
        width: calc(`100% + ${styleUnit((metasVars.spacing.horizontal! as number) * 2)}`),
        ...Mixins.padding({
            all: styleUnit(metasVars.spacing.horizontal),
        }),
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
            fontSize: styleUnit(metasVars.font.size),
            ...Mixins.margin({ all: styleUnit(metasVars.spacing.horizontal) }),
            textDecoration: "none",
        },
    );

    cssOut(".Item.Item .Reactions a, .Item:hover .Reactions a", {
        textDecoration: "none",
        width: "initial",
    });

    cssOut(".FlagMenu > .ReactButton", {
        margin: important(0),
    });

    cssOut(`.Reactions .ReactButton`, {
        color: ColorsUtils.colorOut(metasVars.font.color),
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
