/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const userContentStyles = () => {
    const globalVars = globalVariables();

    const fonts = {
        size: globalVars.fonts.size.medium,
        headings: {
            h1: "2em",
            h2: "1.5em",
            h3: "1.25em",
            h4: "1em",
            h5: ".875em",
            h6: ".85em",
        },
    };

    const list = {
        margin: "2em",
        listDecoration: {
            minWidth: "2em",
        },
    };

    return { fonts, list };
};
