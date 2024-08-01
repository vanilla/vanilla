/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { media } from "@library/styles/styleShim";
import { styleUnit } from "@library/styles/styleUnit";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";

export const socialConnectCSS = () => {
    cssOut(`.DataList-Connections .Connection-Header`, {
        alignItems: "flex-end",
    });

    cssOut(`.DataList-Connections .Gloss`, {
        minHeight: "42px",
        marginTop: styleUnit(5),
        minWidth: styleUnit(200),
    });

    cssOut(`.ActivateSlider`, {
        minWidth: styleUnit(200),
        marginLeft: styleUnit(16),
    });

    const breakPointConnections = 768;

    cssOut(
        `.DataList-Connections .Connection-Header`,
        media(
            { maxWidth: breakPointConnections },
            {
                flexDirection: "column",
                alignItems: "center",
            },
        ),
    );
    cssOut(
        `.Connection-Name`,
        { minHeight: styleUnit(42) },
        media(
            { maxWidth: breakPointConnections },
            {
                justifyContent: "center",
            },
        ),
    );
    cssOut(
        `.DataList-Connections .IconWrap`,
        media(
            { maxWidth: breakPointConnections },
            {
                marginRight: 0,
            },
        ),
    );
    cssOut(
        `.ActivateSlider`,
        media(
            { maxWidth: breakPointConnections },
            {
                minWidth: styleUnit(200),
                marginLeft: 0,
            },
        ),
    );
};
