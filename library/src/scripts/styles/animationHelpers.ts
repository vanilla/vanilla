/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import typestyle from "typestyle";

export const standardAnimations = () => {
    return {
        fadeIn: typestyle.keyframes({
            $debugName: "animation-fadeIn",
            "0%": { opacity: 0 },
            "100%": { opacity: 1 },
        }),
    };
};
