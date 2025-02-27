/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

export const RESOLVE_MAP = {
    "siteSection/name": "Contextual Title",
    "siteSection/description": "Contextual Description",
    "category/name": "Category Title",
    "category/description": "Category Description",

    "knowledgeCategory/name": "Contextual Title",
    "knowledgeBase/name": "Contextual Title",
    "knowledgeBase/description": "Contextual Description",
    knowledgeTitle: "Contextual Title",
    knowledgeDescription: "Contextual Description",
};

/**
 * Resolves any field param for a given key to a placeholder string
 */
const resolveFieldParameter = (key: string, config?: object): object => {
    let resolved = config ?? {};

    // Ensure value is something we can work with
    if (typeof config === "object" && config !== null) {
        if (config.hasOwnProperty(`${key}Type`) && config.hasOwnProperty(`${key}`)) {
            switch (config[`${key}Type`]) {
                // If switching from a param to a static string, reset the field to an empty string
                case "static":
                    {
                        if (typeof resolved[`${key}`] !== "string") {
                            resolved[`${key}`] = "";
                        }
                    }
                    break;
                // If the field is optional, set it as undefined to drop entirely from the produced object
                case "none":
                    {
                        resolved[`${key}`] = undefined;
                    }
                    break;
                default: {
                    // Look up the param
                    resolved[`${key}`] = RESOLVE_MAP[config[`${key}Type`]];
                }
            }
        }
    }
    return resolved;
};

/**
 * Resolves any field param to a placeholder string
 */
export const resolveFieldParams = (config?: object): object => {
    let resolved = ["title", "description"].reduce((previous, currentKey) => {
        return {
            ...previous,
            ...resolveFieldParameter(currentKey, previous),
        };
    }, config ?? {});
    return resolved;
};
