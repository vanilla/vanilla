/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import clone from "lodash-es/clone";
import set from "lodash-es/set";
import unset from "lodash-es/unset";
import get from "lodash-es/get";

type Mapping = Record<string, string | string[]>;

/**
 * Map in legacy variables from the global namespace.
 */
export class GlobalVariableMapping {
    public constructor(private mapping: Mapping) {}
    public map = (into: any, fromVariables: any): any => {
        if (!(typeof into === "object") || !(typeof fromVariables === "object")) {
            return into;
        }

        const result = clone(into);

        const newKeys = Object.keys(this.mapping);
        for (const [newKey, val] of Object.entries(this.mapping)) {
            const altKeys = Array.isArray(val) ? val : [val];
            for (const altKey of altKeys) {
                const existingAlt = get(fromVariables, altKey, null);
                if (existingAlt === null) {
                    // This alt doesn't exist.
                    continue;
                } else {
                    set(result, newKey, clone(existingAlt));
                    if (!newKeys.includes(altKey)) {
                        unset(result, altKey);
                    }
                    break;
                }
            }
        }

        return result;
    };
}

/**
 * Map in legacy variables from the current namespace.
 */
export class LocalVariableMapping extends GlobalVariableMapping {}
