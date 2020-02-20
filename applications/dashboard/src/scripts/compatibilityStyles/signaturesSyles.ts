/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { singleBorder, paddings, margins, unit } from "@library/styles/styleHelpers";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { important } from "csx";

export const signaturesCSS = () => {
    const vars = globalVariables();
    cssOut(`.Content .MessageList .UserSignature`, {
        borderTop: singleBorder(),
        ...paddings({
            top: unit(vars.gutter.size * 2),
            bottom: 0,
        }),
        ...margins({
            vertical: 0,
        }),
    });

    cssOut(`.Content .MessageList .Signature.UserSignature.userContent  > p`, {
        ...margins({
            vertical: important(0),
        }),
        padding: 0,
    });

    cssOut(`.Content .UserSignature`, {});
};
