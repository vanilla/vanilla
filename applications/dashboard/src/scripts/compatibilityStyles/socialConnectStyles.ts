/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { media } from "typestyle";
import { unit } from "@library/styles/styleHelpers";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export const socialConnectCSS = () => {
    cssOut(`.DataList-Connections .Connection-Header`, {
        alignItems: "flex-end",
    });

    cssOut(`.DataList-Connections .Gloss`, {
        minHeight: "42px",
        marginTop: unit(5),
        minWidth: unit(200),
    });

    cssOut(`.ActivateSlider`, {
        minWidth: unit(200),
        marginLeft: unit(16),
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
        { minHeight: unit(42) },
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
                minWidth: unit(200),
                marginLeft: 0,
            },
        ),
    );
};
