import merge from "lodash-es/merge";
import { JsonSchema } from "@vanilla/json-schema-forms";

//get schema object with default values only as props for a widget in order to set them in widget previews
export function extractDataByKeyLookup(schema: JsonSchema, keyToLookup: string, path?: string, currentData?: object) {
    let generatedData = currentData ?? {};
    if (schema && schema.type === "object" && schema !== null) {
        Object.entries(schema.properties).map(([key, value]: [string, JsonSchema]) => {
            if (value.type === "object") {
                extractDataByKeyLookup(value, keyToLookup, path ? `${path}.${key}` : key, generatedData);
            } else if (value[keyToLookup] !== undefined) {
                //we have a path, value is nested somewhere in the object
                if (path) {
                    let keys = [...path.split("."), key],
                        newObjectFromCurrentPath = {};

                    //new object creation logic from path
                    let node = keys.slice(0, -1).reduce(function (memo, current) {
                        return (memo[current] = {});
                    }, newObjectFromCurrentPath);

                    //last key where we'll assign our value
                    node[key] = value[keyToLookup];
                    generatedData = merge(generatedData, newObjectFromCurrentPath);
                } else {
                    //its first level value, we just assign it to our object
                    generatedData[key] = value[keyToLookup];
                }
            }
        });
    }
    return generatedData;
}
