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
