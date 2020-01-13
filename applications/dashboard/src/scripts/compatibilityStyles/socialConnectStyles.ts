/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cssRule, media } from "typestyle";
import { unit } from "@library/styles/styleHelpers";

export const socialConnectCSS = () => {
    cssRule(`.DataList-Connections .Connection-Header`, {
        alignItems: "flex-end",
    });

    cssRule(`.Gloss`, {
        minHeight: "42px",
        marginTop: unit(5),
        minWidth: unit(200),
    });

    cssRule(`.ActivateSlider`, {
        minWidth: unit(200),
        marginLeft: unit(16),
    });

    const breakPointConnections = 768;

    cssRule(
        `.DataList-Connections .Connection-Header`,
        media(
            { maxWidth: breakPointConnections },
            {
                flexDirection: "column",
                alignItems: "center",
            },
        ),
    );
    cssRule(
        `.Connection-Name`,
        { minHeight: unit(42) },
        media(
            { maxWidth: breakPointConnections },
            {
                justifyContent: "center",
            },
        ),
    );
    cssRule(
        `.DataList-Connections .IconWrap`,
        media(
            { maxWidth: breakPointConnections },
            {
                marginRight: 0,
            },
        ),
    );
    cssRule(
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
