/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { t } from "@vanilla/i18n";
import { JsonSchema } from "@vanilla/json-schema-forms";

/**
 * Determine if a widget schema should be transformed to display the
 * apiParams as an accordion item in the edit form.
 *
 * The label on this section is normally "Display Options"
 */
export function showDisplayOptions(description: string): boolean {
    const widgets = ["Events", "Discussions", "Announcements", "Questions", "Ideas", "Articles", "Discussion List"];
    return widgets.includes(description);
}

/**
 * Any requires schema augmentations needed to better display the widget options form
 * should occur in this function
 */
export function widgetsSchemaTransformer(
    schema: JsonSchema,
    middlewares: ILayoutCatalog["middlewares"],
    initialValue?: any,
): JsonSchema {
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
     * Tabs-specific transform
     */

    if (schema.description === "Tabs") {
        transformedSchema = {
            ...schema,
            properties: {
                ...schema.properties,
                tabConfiguration: {
                    ...schema.properties.tabConfiguration,
                    "x-control": {
                        description: t("Add/Edit Tabs Configuration"),
                        label: t("Tabs"),
                        inputType: "modal",
                        modalTriggerLabel: t("Edit"),
                        modalContent: {
                            ...schema.properties.tabConfiguration["x-control"],
                        },
                    },
                },
            },
        };
    }

    /**
     * Site Totals specific transform
     */
    if (schema.description === "Site Totals") {
        const { apiParams } = schema.properties;
        const tempApiParams = {
            ...apiParams,
            properties: {
                ...apiParams.properties,
                counts: {
                    ...apiParams.properties.counts,
                    "x-control": {
                        description: t("Add/Edit Site Totals Configuration"),
                        label: t("Site Metrics"),
                        inputType: "modal",
                        modalTriggerLabel: t("Edit"),
                        modalContent: {
                            ...apiParams.properties.counts["x-control"],
                        },
                    },
                },
            },
        };

        transformedSchema = {
            ...schema,
            properties: {
                ...schema.properties,
                apiParams: tempApiParams,
            },
        };
    }

    /**
     * Discussions specific transform
     */
    if (["Discussions", "Announcements", "Questions", "Ideas", "Discussion List"].includes(schema.description)) {
        const shouldNotHaveMetaOptions =
            initialValue &&
            initialValue.containerOptions &&
            initialValue.containerOptions.displayType &&
            initialValue.containerOptions.displayType !== WidgetContainerDisplayType.LIST;

        //if display type is not list, meta options are limited always rendered as icons
        if (shouldNotHaveMetaOptions) {
            transformedSchema = {
                ...schema,
                properties: {
                    ...schema.properties,
                    discussionOptions: {
                        ...schema.properties.discussionOptions,
                        properties: {
                            ...schema.properties.discussionOptions.properties,
                            metas: {
                                "x-control": {
                                    label: t("Meta Options"),
                                    description: "",
                                },
                                description: "Configure meta options.",
                                properties: {
                                    ...schema.properties.discussionOptions.properties.metas.properties,
                                    asIcons: {
                                        default: true,
                                        description: "Metas as Icons.",
                                        type: "boolean",
                                    },
                                    display: {
                                        description: "Display metas",
                                        type: "object",
                                        properties: {
                                            ...schema.properties.discussionOptions.properties.metas.properties.display
                                                .properties,
                                            category: {
                                                description: "Enable category option in meta.",
                                                type: "boolean",
                                            },
                                            startedByUser: {
                                                description: "Enable author option in meta.",
                                                type: "boolean",
                                            },
                                            lastCommentDate: {
                                                description: "Enable last comment date option in meta.",
                                                type: "boolean",
                                            },
                                            userTags: {
                                                description: "Enable user tags option in meta.",
                                                type: "boolean",
                                            },
                                            unreadCount: {
                                                description: "Enable unread count option in meta.",
                                                type: "boolean",
                                            },
                                        },
                                    },
                                },
                                type: "object",
                            },
                        },
                    },
                },
            };
        }

        //its the asset, some options should not be available in widget settings/configuration
        if (schema.description === "Discussion List") {
            transformedSchema = {
                ...transformedSchema,
                properties: {
                    ...transformedSchema.properties,
                    //no followed for discussion list asset, it won't appear
                    apiParams: {
                        ...transformedSchema.properties.apiParams,
                        properties: {
                            ...transformedSchema.properties.apiParams.properties,
                            followed: {
                                type: "boolean",
                            },
                        },
                    },
                    //no link/carousel display type and viewAll option for discussion list asset
                    containerOptions: {
                        ...transformedSchema.properties.containerOptions,
                        properties: {
                            ...transformedSchema.properties.containerOptions.properties,
                            displayType: {
                                ...transformedSchema.properties.containerOptions.properties.displayType,
                                enum: ["grid", "list"],
                                "x-control": {
                                    ...transformedSchema.properties.containerOptions.properties.displayType[
                                        "x-control"
                                    ],
                                    choices: {
                                        staticOptions: {
                                            list: "List",
                                            grid: "Grid",
                                        },
                                    },
                                },
                            },
                            viewAll: {
                                type: "object",
                                properties: {
                                    showViewAll: {
                                        type: "boolean",
                                    },
                                },
                            },
                        },
                    },
                },
            };
        }
    }

    // Determine if the `featuredImage` and `fallbackImage` properties should be included in the `apiParams` schema
    if (showDisplayOptions(schema.description)) {
        const { apiParams } = transformedSchema.properties;
        const { featuredImage, fallbackImage, ...apiSchema } = apiParams.properties;

        const transformedApiSchema = {
            ...apiSchema,
        };

        if (initialValue.containerOptions?.displayType !== "link") {
            transformedApiSchema.featuredImage = featuredImage;
            transformedApiSchema.fallbackImage = fallbackImage;
        }

        transformedSchema = {
            ...transformedSchema,
            properties: {
                ...transformedSchema.properties,
                apiParams: {
                    ...apiParams,
                    properties: transformedApiSchema,
                },
            },
        };
    }

    /**
     * Add middlewares to schema
     */
    let middlewareSchemaProperties = Object.entries(middlewares).reduce((accumulator, [middlewareName, { schema }]) => {
        return { ...accumulator, [middlewareName]: schema };
    }, {});

    //for guest widget we won't have the role dropdown, instead we show the tooltip
    if (schema.description === "Guest Sign In") {
        middlewareSchemaProperties = {
            ...middlewareSchemaProperties,
            "role-filter": {
                ...middlewareSchemaProperties["role-filter"],
                properties: {
                    ...middlewareSchemaProperties["role-filter"].properties,
                    roleIDs: {
                        ...middlewareSchemaProperties["role-filter"].properties.roleIDs,
                        "x-control": {
                            ...middlewareSchemaProperties["role-filter"].properties.roleIDs["x-control"],
                            tooltip: "The guest widget only appears to guest users.",
                            inputType: "empty",
                        },
                    },
                },
            },
        };
    }

    //its the asset, no conditions section for this one
    if (schema.description !== "Discussion List") {
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
    }

    return transformedSchema;
}
