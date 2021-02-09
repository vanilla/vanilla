/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { JSONSchema4 } from "json-schema";
import { VariableParser } from "..";
import { IVariable, IVariableDoc, IVariableGroup } from "./VariableParser";
import { spaceshipCompare } from "@vanilla/utils";
import { JsonSchemaFlatAdapter } from "./JsonSchemaFlatAdapter";

const ROOT_KEY = "$root";

type IParentsByKey = Record<string, string | null>;
type IChildrenByKey = Record<string | symbol, string[]>;

/**
 * Variable adapter to created a nested JSON schema.
 */
export class JsonSchemaNestedAdapter {
    private varsByKey: Record<string, IVariable> = {};
    private groupsByKey: Record<string, IVariableGroup> = {};
    private childrenByKey: IChildrenByKey = {};

    public constructor(variables: IVariable[], groups: IVariableGroup[]) {
        this.varsByKey = Object.fromEntries(
            variables.sort(this.sortVarDocsByKey).map((variable) => [variable.key, variable]),
        );
        this.groupsByKey = Object.fromEntries(
            groups.sort(this.sortVarDocsByKey).map((varGroup) => [varGroup.key, varGroup]),
        );
        this.ensureGroups();
        this.calcParentChildRelations();
    }

    /**
     * Get the contents as of the adapter as a JSON schema object.
     */
    public asJsonSchema(): JSONSchema4 {
        // Assemble the tree.
        const base: JSONSchema4 = {
            type: "object",
            $schema: "http://json-schema.org/schema",
            properties: {},
        };
        const finalSchema = this.applyChildrenInSchema(base, this.childrenByKey[ROOT_KEY]);

        return finalSchema;
    }

    /**
     * Calculate the parents and children of all elements.
     */
    private calcParentChildRelations() {
        // Get all of the keys (now that we ensured the groups.)
        const allKeys = [...Object.keys(this.groupsByKey), ...Object.keys(this.varsByKey)];

        // Calculate all of the children.
        const parentsByKey: IParentsByKey = {};
        for (const key of allKeys) {
            parentsByKey[key] = this.calcParentGroupKey(key);
        }

        const childrenByKey: IChildrenByKey = {
            [ROOT_KEY]: [],
        };
        for (const [childKey, parentKey] of Object.entries(parentsByKey)) {
            if (parentKey === null) {
                // This is in the root.
                childrenByKey[ROOT_KEY].push(childKey);
                continue;
            }

            if (!(parentKey in childrenByKey)) {
                childrenByKey[parentKey] = [];
            }
            childrenByKey[parentKey].push(childKey);
        }

        this.childrenByKey = childrenByKey;
    }

    /**
     * Sort function to alphabetically sort docs by their key.
     */
    private sortVarDocsByKey(docA: IVariableDoc, docB: IVariableDoc) {
        return spaceshipCompare(docA.key, docB.key);
    }

    /**
     * Recursively lookup and and apply children into the JSON schema.
     *
     * @param schema The schema to build on.
     * @param childKeys The keys of the children to add into the schema. Can be groups or vars.
     */
    private applyChildrenInSchema(schema: JSONSchema4, childKeys: string[]): JSONSchema4 {
        for (const childKey of childKeys) {
            const subKey = childKey.split(".").pop();
            if (!subKey) {
                continue;
            }
            // Make sure we have properties defined.
            schema.properties = schema.properties ?? {};

            if (childKey in this.varsByKey) {
                schema.properties[subKey] = JsonSchemaFlatAdapter.varAsSchema(this.varsByKey[childKey]);
            } else if (childKey in this.groupsByKey) {
                const groupSchema = JsonSchemaFlatAdapter.varGroupAsSchema(this.groupsByKey[childKey]);

                // This is a group and may have children.
                const groupChildKeys = this.childrenByKey[childKey] ?? [];
                const groupWithChildrenSchema = this.applyChildrenInSchema(groupSchema, groupChildKeys);
                schema.properties[subKey] = groupWithChildrenSchema;
            } else {
                // This really shouldn't happen if all of our parents were created properly.
                throw new Error(`Could not find variable or group with key ${childKey}`);
            }
        }

        return schema;
    }

    /**
     * Ensure all variables have parent groups, creating synthetic records where necessary.
     */
    private ensureGroups() {
        this.ensureParentGroupsForKeys([...Object.keys(this.groupsByKey), ...Object.keys(this.varsByKey)]);
    }

    /**
     * Ensure that all of the variable of group keys have the existing parent groups.
     *
     * @param keys
     */
    private ensureParentGroupsForKeys(keys: string[]) {
        for (const key of keys) {
            const parentGroupKey = this.calcParentGroupKey(key);
            if (parentGroupKey === null) {
                // This is already a top level key.
                continue;
            }

            if (parentGroupKey in this.groupsByKey) {
                // This parent already exists.
                continue;
            }

            // Let's create a "synthetic" parent group.
            this.groupsByKey[parentGroupKey] = {
                key: parentGroupKey,
                title: VariableParser.varNameToTitle(parentGroupKey),
            };

            // Since we created it, we may have to create it's parent.
            this.ensureParentGroupsForKeys([parentGroupKey]);
        }
    }

    /**
     * Calculate a parent group key from an existing key.
     *
     * @param key The key to calculate from.
     *
     * @return The parent group key or null if we already are a parent group.
     */
    private calcParentGroupKey(key: string): string | null {
        const splitKey = key.trim().split(".");
        splitKey.pop();
        const parentGroupKey = splitKey.join(".");
        if (parentGroupKey.length > 0) {
            return parentGroupKey;
        } else {
            return null;
        }
    }
}
