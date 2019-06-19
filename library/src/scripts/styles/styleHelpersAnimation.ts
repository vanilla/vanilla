/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";

export const defaultTransition = (...properties) => {
    const vars = globalVariables();
    properties = properties.length === 0 ? ["all"] : properties;
    return {
        transition: `${properties.map((prop, index) => {
            return `${prop} ${vars.animation.defaultTiming} ${vars.animation.defaultEasing}${
                index === properties.length ? ", " : ""
            }`;
        })}`,
    };
};
