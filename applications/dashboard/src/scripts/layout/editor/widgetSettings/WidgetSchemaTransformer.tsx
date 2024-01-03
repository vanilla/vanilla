/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { t } from "@vanilla/i18n";
import { IFormControl, JsonSchema } from "@vanilla/json-schema-forms";
import React from "react";

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
 * Transforms schema with less meta options
 */
function transformMetaOptions(currentSchema: JsonSchema, initialValue: any): JsonSchema {
    const optionType = ["Categories", "Category List"].includes(currentSchema.description)
        ? "categoryOptions"
        : "discussionOptions";

    let newSchema = {
        ...currentSchema,
        properties: {
            ...currentSchema.properties,
        },
    };

    const newMetaProperties =
        optionType === "discussionOptions"
            ? {
                  ...currentSchema?.properties[optionType].properties.metas.properties.display.properties,
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
              }
            : {
                  ...currentSchema?.properties[optionType].properties.metas.properties.display.properties,
                  lastPostName: {
                      description: "Enable last comment or discussion name in meta.",
                      type: "boolean",
                  },
                  lastPostAuthor: {
                      description: "Enable last comment or discussion author in meta.",
                      type: "boolean",
                  },
                  lastPostDate: {
                      description: "Enable last comment or discussion date in meta.",
                      type: "boolean",
                  },
                  subcategories: {
                      description: "Enable subcategories in meta.",
                      type: "boolean",
                  },
              };

    newSchema.properties[optionType] = {
        ...currentSchema?.properties[optionType],
        properties: {
            ...currentSchema?.properties[optionType].properties,
            metas: {
                "x-control": {
                    label: t("Meta Options"),
                    description: "",
                },
                description: "Configure meta options.",
                properties: {
                    ...currentSchema?.properties[optionType].properties.metas.properties,
                    asIcons: {
                        default: true,
                        description: "Metas as Icons.",
                        type: "boolean",
                    },
                    display: {
                        description: "Display metas",
                        type: "object",
                        properties: newMetaProperties,
                    },
                },
                type: "object",
            },
        },
    };

    // no follow button on grid/carousel
    if (optionType === "categoryOptions") {
        newSchema.properties[optionType] = {
            ...newSchema?.properties[optionType],
            properties: {
                ...newSchema?.properties[optionType].properties,
                followButton: {
                    properties: {
                        ...newSchema?.properties[optionType].properties.followButton.properties,
                        display: {
                            default: false,
                            description: "Show follow category action button.",
                            type: "boolean",
                        },
                    },
                },
            },
        };
    }

    // no metas or other options for links
    if (initialValue?.containerOptions?.displayType === WidgetContainerDisplayType.LINK) {
        newSchema.properties[optionType].properties = {
            metas: {
                "x-control": {
                    inputType: "custom",
                    // eslint-disable-next-line react/display-name
                    component: () => <>{t("No item options are available for Link display type.")}</>,
                },
            },
        };
    }

    return newSchema;
}

/**
 * Any requires schema augmentations needed to better display the widget options form
 * should occur in this function
 */
export function widgetsSchemaTransformer(
    schema: JsonSchema,
    middlewares: ILayoutCatalog["middlewares"],
    initialValue?: any,
): {
    transformedSchema: JsonSchema;
    value: any;
} {
    let transformedSchema = schema;
    let value = initialValue;

    /**
     * Quick Links specific transform
     */
    if (schema.description === "Quick Links") {
        transformedSchema = {
            ...schema,
            properties: {
                ...schema.properties,
                links: {
                    ...schema?.properties?.links,
                    "x-control": {
                        description: t("Add/Edit quick links"),
                        label: t("Links List"),
                        inputType: "modal",
                        modalTriggerLabel: t("Edit"),
                        modalContent: {
                            ...(schema?.properties?.links?.["x-control"] as IFormControl),
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
                    ...schema?.properties?.tabConfiguration,
                    "x-control": {
                        description: t("Add/Edit Tabs Configuration"),
                        label: t("Tabs"),
                        inputType: "modal",
                        modalTriggerLabel: t("Edit"),
                        modalContent: {
                            ...(schema?.properties?.tabConfiguration?.["x-control"] as IFormControl),
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
        const { apiParams } = schema?.["properties"] ?? {};
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
     * Categories/Subcommunities specific transform
     */
    if (
        schema.description === "Category List" ||
        schema.description === "Categories" ||
        schema.description === "Subcommunities"
    ) {
        const itemOptionsContentTypeOptions: {
            "title-background": string | undefined;
            "title-description": string;
            "title-description-icon": string | undefined;
            "title-description-image": string | undefined;
        } = {
            "title-background": "Background",
            "title-description": "None",
            "title-description-icon": "Icon",
            "title-description-image": "Image",
        };

        if (initialValue.containerOptions?.displayType === WidgetContainerDisplayType.LIST) {
            delete itemOptionsContentTypeOptions["title-background"];

            // adjust the current value so its not from not supported list
            if (value.itemOptions?.contentType && value.itemOptions?.contentType === "title-background") {
                value = {
                    ...value,
                    itemOptions: {
                        ...value.itemOptions,
                        contentType: "title-description-icon",
                    },
                };
            }
        }

        if (initialValue.containerOptions?.displayType === WidgetContainerDisplayType.LINK) {
            delete itemOptionsContentTypeOptions["title-background"];
            delete itemOptionsContentTypeOptions["title-description-icon"];
            delete itemOptionsContentTypeOptions["title-description-image"];

            // adjust the current value so its not from not supported list
            if (value.itemOptions?.contentType) {
                value = {
                    ...value,
                    itemOptions: {
                        ...value.itemOptions,
                        contentType: "title-description",
                    },
                };
            }
        }

        const itemOptionsContentTypeXControl =
            initialValue.containerOptions?.displayType !== WidgetContainerDisplayType.LINK
                ? {
                      ...transformedSchema.properties.itemOptions.properties.contentType["x-control"],
                      choices: {
                          staticOptions: itemOptionsContentTypeOptions,
                      },
                  }
                : {
                      inputType: "custom",
                      // eslint-disable-next-line react/display-name
                      component: () => <>{t("No item options are available for Link display type.")}</>,
                  };

        transformedSchema = {
            ...transformedSchema,
            properties: {
                ...transformedSchema.properties,
                // depending on what is containerOptions displayType, we should determine image versions for itemOptions
                itemOptions: {
                    ...transformedSchema.properties.itemOptions,
                    properties: {
                        ...transformedSchema.properties.itemOptions.properties,
                        contentType: {
                            ...transformedSchema.properties.itemOptions.properties.contentType,
                            enum: Object.keys(itemOptionsContentTypeOptions),
                            "x-control": itemOptionsContentTypeXControl,
                        },
                        //make sure there are no fallback inputs rest for Link after transformations
                        fallbackIcon: {
                            ...transformedSchema.properties.itemOptions.properties.fallbackIcon,
                            "x-control":
                                initialValue.containerOptions?.displayType === WidgetContainerDisplayType.LINK
                                    ? undefined
                                    : transformedSchema.properties.itemOptions.properties.fallbackIcon["x-control"],
                        },
                    },
                },
            },
        };

        // kludge for categories widget filter value, from release 2023.022 we changed the filter options for lot of widgets including category widget
        // so we want th previous value to be converted to new one
        if (schema.description === "Categories") {
            // was filtered by "Specific Categories" before, should be "Featured Categories" now
            if (value.apiParams?.filter === "category" && value.apiParams?.categoryID?.length > 0) {
                value = {
                    ...value,
                    apiParams: {
                        ...value.apiParams,
                        filter: "featured",
                        featuredCategoryID: value.apiParams.categoryID,
                        categoryID: null,
                    },
                };
            }
            // was filtered by "Parent Category" before, should be "Category" now
            if (value.apiParams?.filter === "parentCategory" && value.apiParams?.parentCategoryID) {
                value = {
                    ...value,
                    apiParams: {
                        ...value.apiParams,
                        filter: "category",
                        categoryID: value.apiParams?.parentCategoryID,
                        parentCategoryID: null,
                    },
                };
            }
            // was filtered by "Subcommunity" before, should be "Subcommunity" now, the label was the same, the value should be changed, with "filterSubcommunitySubType" set to "set"
            if (value.apiParams?.filter === "siteSection" && value.apiParams?.siteSectionID) {
                value = {
                    ...value,
                    apiParams: {
                        ...value.apiParams,
                        filter: "subcommunity",
                        filterSubcommunitySubType: "set",
                    },
                };
            }
            // was filtered by "Current Subcommunity", should be "Subcommunity" now, with "filterSubcommunitySubType" set to "contextual"
            if (value.apiParams?.filter === "currentSiteSection") {
                value = {
                    ...value,
                    apiParams: {
                        ...value.apiParams,
                        filter: "subcommunity",
                        filterSubcommunitySubType: "contextual",
                    },
                };
            }
            // was filtered by "Current Category", should be "None" now, as we don't have this anymore in the filter
            if (value.apiParams?.filter === "currentCategory") {
                value = {
                    ...value,
                    apiParams: {
                        ...value.apiParams,
                        filter: "none",
                    },
                };
            }
        }
    }

    /**
     * When display type is not list we limit metas for discussions and categories.
     */
    if (
        [
            "Discussions",
            "Announcements",
            "Questions",
            "Ideas",
            "Discussion List",
            "Categories",
            "Category List",
        ].includes(schema.description)
    ) {
        // for categories widget the default is grid, so even if we don't have displayType, we should limit metas
        const shouldLimitMetaOptions =
            schema.description === "Categories"
                ? initialValue?.containerOptions?.displayType !== WidgetContainerDisplayType.LIST
                : initialValue?.containerOptions?.displayType &&
                  initialValue?.containerOptions?.displayType !== WidgetContainerDisplayType.LIST;

        //if display type is not list, meta options are limited and always rendered as icons
        if (shouldLimitMetaOptions) {
            transformedSchema = transformMetaOptions(transformedSchema, initialValue);
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
    if (!initialValue.isAsset) {
        transformedSchema = {
            ...transformedSchema,
            properties: {
                ...transformedSchema.properties,
                $middleware: {
                    type: "object",
                    properties: middlewareSchemaProperties,
                    "x-control": {
                        label: t("Conditions"),
                        description: "",
                    },
                },
            },
        };
    }

    // Special handling for banner since the title could be populated by variables
    if (["Banner", "Content Banner"].includes(schema.description)) {
        // Convert old empty prop to new contextual param value
        value = convertContextualValue("title", schema, initialValue, value);
        value = convertContextualValue("description", schema, initialValue, value);

        value.showTitle = value?.titleType !== "none";
        value.showDescription = value?.descriptionType !== "none";
    }

    const resolvedTitle = setParamField("title", transformedSchema, value);
    const resolvedDescription = setParamField("description", resolvedTitle.resolvedSchema, resolvedTitle.resolvedValue);
    transformedSchema = resolvedDescription.resolvedSchema;
    value = resolvedDescription.resolvedValue;

    return { transformedSchema, value };
}

/**
 * This function converts an empty string value (previously used to denote a contextual value) to the
 * standard schema of `{ $hydrate: "param", ref: "REF_VALUE"}`
 *
 */
function convertContextualValue(key: string, schema: JsonSchema, initialValue, value) {
    let payload = value;

    // First check if this is an old config without either field or a type field and convert it
    if (!initialValue.hasOwnProperty(`${key}`) && !initialValue.hasOwnProperty(`${key}Type`)) {
        payload[`${key}Type`] = schema.properties?.[`${key}Type`]?.default;
        payload[`${key}`] = { $hydrate: "param", ref: schema.properties?.[`${key}Type`]?.default };
    }

    if (initialValue[`${key}`] === "" && !initialValue.hasOwnProperty(`${key}Type`)) {
        if (schema.properties?.[`${key}Type`]?.default) {
            payload[`${key}Type`] = schema.properties?.[`${key}Type`]?.default;
            payload[`${key}`] = { $hydrate: "param", ref: schema.properties?.[`${key}Type`]?.default };
        }
    }
    return payload;
}

/**
 * Set the correct param schema for a selected type
 * @param key - Key of the field being set. Expects a a field of `keyType` as the selector
 * @param schema - The JSONSchema
 * @param value - The current value object
 */
export function setParamField(
    key: string,
    schema: JsonSchema,
    value: any,
): { resolvedSchema: JsonSchema; resolvedValue: any } {
    let resolvedSchema = schema;
    let resolvedValue = value;
    if (resolvedSchema.properties.hasOwnProperty(`${key}Type`)) {
        switch (resolvedValue[`${key}Type`]) {
            // Handling for custom titles & descriptions
            case "static":
                {
                    if (typeof resolvedValue[`${key}`] !== "string") {
                        resolvedValue[`${key}`] = "";
                    }
                }
                break;
            // Must set resolved value to empty here,
            // otherwise the default will be hydrated
            case "none":
                {
                    resolvedValue[`${key}`] = "";
                }
                break;
            // Default to whatever param ref the BE passes
            default: {
                // Check that we have a value to resolve, if we don't default to a string
                if (resolvedValue[`${key}Type`]) {
                    resolvedValue[`${key}`] = { $hydrate: "param", ref: resolvedValue[`${key}Type`] };
                } else {
                    if (typeof resolvedValue[`${key}`] === "string" && resolvedValue[`${key}`].length > 1) {
                        resolvedValue[`${key}Type`] = "static";
                    } else {
                        resolvedValue[`${key}Type`] = "none";
                        resolvedValue[`${key}`] = "";
                    }
                }
            }
        }
    }
    return { resolvedSchema, resolvedValue };
}
