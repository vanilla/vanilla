/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { important } from "csx";
import { Mixins } from "@library/styles/Mixins";

export const signaturesCSS = () => {
    const vars = globalVariables();
    cssOut(`.Content .MessageList .UserSignature`, {
        borderTop: singleBorder(),
        ...Mixins.padding({
            top: styleUnit(vars.gutter.size * 2),
            bottom: 0,
        }),
        ...Mixins.margin({
            vertical: 0,
        }),
    });

    cssOut(`.Content .MessageList .Signature.UserSignature.userContent  > p`, {
        ...Mixins.margin({
            vertical: important(0),
        }),
        padding: 0,
    });

    cssOut(`.Content .UserSignature`, {});
};
