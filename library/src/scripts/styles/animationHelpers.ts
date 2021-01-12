/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { keyframes } from "@library/styles/styleShim";

export const standardAnimations = () => {
    return {
        fadeIn: keyframes({
            label: "animation-fadeIn",
            "0%": { opacity: 0 },
            "100%": { opacity: 1 },
        }),
    };
};
