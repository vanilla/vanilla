/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { NavigationLinksModal } from "@dashboard/components/navigation/NavigationLinksModal";
import { ILayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import { t } from "@vanilla/i18n";
import { IControlProps, JsonSchema } from "@vanilla/json-schema-forms";
import React, { useState } from "react";

/**
 * Any requires schema augmentations needed to better display the widget options form
 * should occur in this function
 */
export function widgetsSchemaTransformer(schema: JsonSchema, middlewares: ILayoutCatalog["middlewares"]): JsonSchema {
    let transformedSchema = schema;

    /**
     * Quick Links specific transform
     */
    if (schema.description === "Quick Links") {
        transformedSchema = {
            ...schema,
            properties: {
                ...schema.properties,
                links: {
                    ...schema.properties.links,
                    "x-control": {
                        description: t("Add/Edit quick links"),
                        label: t("Links List"),
                        inputType: "modal",
                        modalTriggerLabel: t("Edit"),
                        modalContent: {
                            ...schema.properties.links["x-control"],
                        },
                    },
                },
            },
        };
    }

    /**
     * Add middlewares to schema
     */
    const middlewareSchemaProperties = Object.entries(middlewares).reduce(
        (accumulator, [middlewareName, { schema }]) => {
            return { ...accumulator, [middlewareName]: schema };
        },
        {},
    );

    transformedSchema = {
        ...transformedSchema,
        properties: {
            ...transformedSchema.properties,
            $middleware: {
                type: "object",
                properties: middlewareSchemaProperties,
                "x-control": {
                    label: t("Conditions"),
                },
            },
        },
    };

    return transformedSchema;
}

/**
 * This constant contains the condition when a custom component should be displayed
 * and the custom component itself in the callback field
 */
export const QUICK_LINKS_LIST_AS_MODAL = {
    condition: (props: IControlProps): boolean => {
        return props.control.inputType === "modal" && props.rootSchema.description === "Quick Links";
    },
    callback: function DashboardModalControl(props: IControlProps) {
        const { control, instance } = props;
        const [isOpen, setOpen] = useState(false);
        return (
            <>
                <div className="input-wrap">
                    <Button
                        onClick={() => {
                            setOpen(true);
                        }}
                        buttonType={ButtonTypes.STANDARD}
                    >
                        {control["modalTriggerLabel"]}
                    </Button>
                </div>
                <NavigationLinksModal
                    title={"Quick Links"}
                    isNestingEnabled={false}
                    navigationItems={(instance ?? quickLinksVariables().links ?? []) as any}
                    isVisible={isOpen}
                    onCancel={() => setOpen(false)}
                    onSave={(newData) => {
                        props.onChange(newData);
                        setOpen(false);
                    }}
                />
            </>
        );
    },
};
