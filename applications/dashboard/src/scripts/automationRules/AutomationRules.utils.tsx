/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    AddEditAutomationRuleParams,
    AutomationRuleActionType,
    AutomationRuleFormValues,
    AutomationRuleTriggerType,
    IAutomationRule,
    IAutomationRuleAction,
    IAutomationRuleDispatch,
    IAutomationRulesCatalog,
} from "@dashboard/automationRules/AutomationRules.types";
import mapProfileFieldsToSchemaForFilterForm from "@dashboard/components/panels/mapProfileFieldsToSchemaForFilterForm";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { t } from "@vanilla/i18n";
import { JsonSchema } from "@vanilla/json-schema-forms";
import cloneDeep from "lodash/cloneDeep";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { compare } from "@vanilla/utils";

export const RECIPES_MAX_LIMIT = 150;

export const EMPTY_AUTOMATION_RULE_FORM_VALUES: AutomationRuleFormValues = {
    trigger: {
        triggerType: "",
        triggerValue: {},
    },
    action: {
        actionType: "",
        actionValue: {},
    },
};

/**
 *  Checks if the trigger is time based
 */
export const isTimeBasedTrigger = (
    triggerType?: AutomationRuleTriggerType | string,
    catalog?: IAutomationRulesCatalog,
): boolean => {
    if (triggerType && catalog) {
        return catalog.triggers[triggerType]?.schema?.properties?.triggerTimeDelay ? true : false;
    }
    return false;
};

/**
 *  Checks if the trigger has postType field
 */
export const hasPostType = (
    triggerType?: AutomationRuleTriggerType | string,
    catalog?: IAutomationRulesCatalog,
): boolean => {
    if (triggerType && catalog) {
        return catalog.triggers[triggerType]?.schema?.properties?.postType ? true : false;
    }
    return false;
};

/**
 *  Returns additional settings from trigger schema
 */
export const getTriggerAdditionalSettings = (
    triggerType?: AutomationRuleTriggerType | string,
    catalog?: IAutomationRulesCatalog,
) => {
    if (triggerType && catalog) {
        return Object.keys(catalog?.triggers[triggerType]?.schema?.properties?.additionalSettings ?? {});
    }
    return null;
};

/**
 *  Checks if action has dynamic schema and returns dynamic schema params
 */
export const getActionDynamicSchemaParams = (
    formValues: AutomationRuleFormValues,
    catalog?: IAutomationRulesCatalog,
) => {
    const selectedActionType = formValues.action?.actionType as AutomationRuleActionType;
    const dynamicSchemaParamKeys = catalog?.actions[selectedActionType]?.dynamicSchemaParams;

    const actionDynamicSchemaParamsArr =
        dynamicSchemaParamKeys &&
        dynamicSchemaParamKeys.reduce((acc: any[], paramKey: string) => {
            // find matching action value key, in some cases it might not exactly match the param key, but contain it
            const actualActionValueKey = Object.keys(formValues.action?.actionValue ?? {}).find(
                (value) => value === paramKey || value.includes(paramKey),
            );
            if (actualActionValueKey) {
                acc.push([paramKey, formValues.action?.actionValue[actualActionValueKey]]);
            }
            return acc;
        }, []);
    const hasRequiredParamValues =
        dynamicSchemaParamKeys?.length && dynamicSchemaParamKeys?.length === actionDynamicSchemaParamsArr?.length;

    if (dynamicSchemaParamKeys && hasRequiredParamValues) {
        return {
            actionType: selectedActionType,
            params: Object.fromEntries(actionDynamicSchemaParamsArr ?? []),
        };
    }
    return null;
};

/**
 *  Mainly adjustments for profile field form fields
 */
export function mapApiValuesToFormValues(
    recipe?: IAutomationRule | IAutomationRuleDispatch,
    catalog?: IAutomationRulesCatalog,
    profileFields?: ProfileField[],
): AutomationRuleFormValues {
    if (recipe) {
        // profile field adjustments
        let triggerValue = recipe.trigger.triggerValue;
        if (triggerValue?.profileField) {
            const profileFieldApiName = Object.keys(triggerValue?.profileField)[0];
            const isNumericTokensProfileField = profileFields?.find(
                (field) =>
                    field.apiName === profileFieldApiName &&
                    field.formType === "tokens" &&
                    field.dataType === "number[]",
            );

            triggerValue = {
                ...triggerValue,
                profileField: profileFieldApiName,
                [profileFieldApiName]: isNumericTokensProfileField
                    ? // we do this so our autocomplete does not mess up the values, it always expects an string values
                      triggerValue?.profileField[profileFieldApiName].map((value) => value.toString())
                    : triggerValue?.profileField[profileFieldApiName],
            };
        }

        // if there are trigger values that belong to additional settings, move them under additionalSettings
        const triggerAdditionalSettings = getTriggerAdditionalSettings(recipe.trigger.triggerType, catalog) ?? [];
        const additionalSettings: any = {};

        if (
            triggerAdditionalSettings.length > 0 &&
            triggerAdditionalSettings.some((key) => typeof triggerValue[key] !== "undefined")
        ) {
            Object.keys(triggerValue).forEach((key) => {
                if (!additionalSettings.triggerValue) {
                    additionalSettings.triggerValue = {};
                }
                if (triggerAdditionalSettings.includes(key)) {
                    additionalSettings.triggerValue[key] = triggerValue[key];
                }
            });
        }

        return {
            trigger: {
                triggerType: recipe.trigger.triggerType,
                triggerValue: triggerValue,
            },
            action: { ...recipe.action },
            ...(Object.keys(additionalSettings).length > 0 && { additionalSettings: additionalSettings }),
        };
    }
    return EMPTY_AUTOMATION_RULE_FORM_VALUES;
}

/**
 *  Form fields to api query format
 */
export function mapFormValuesToApiValues(values: AutomationRuleFormValues): AddEditAutomationRuleParams {
    const adjustedValues = cloneDeep(values);
    if (adjustedValues.trigger?.triggerValue?.profileField) {
        const profileFieldApiName = adjustedValues.trigger.triggerValue.profileField;
        const profileFieldValue = adjustedValues.trigger.triggerValue[profileFieldApiName];
        if (profileFieldApiName) {
            adjustedValues.trigger.triggerValue.profileField = {
                [profileFieldApiName]: profileFieldValue ?? null,
            };
        }
    }
    // we might want to add more recordTypes here, when collections support more
    if (adjustedValues.action?.actionValue?.collectionID) {
        adjustedValues.action.actionValue.recordType = "discussion";
    }

    // bring additional settings triggerValue fields to the top level triggerValue
    if (adjustedValues.trigger && adjustedValues.additionalSettings?.triggerValue) {
        adjustedValues.trigger.triggerValue = {
            ...adjustedValues.trigger.triggerValue,
            ...adjustedValues.additionalSettings.triggerValue,
        };
    }

    return adjustedValues;
}

/**
 *  Converts time threshold and time unit to api values
 */
export function convertTimeIntervalToApiValues(
    timeThreshold: number,
    timeUnit: "hour" | "day" | "week" | "year",
    dateToCountFrom?: Date, // this one is mainly for testing purposes
): Date {
    let multipleBy = 1;
    const now = dateToCountFrom?.getTime() ?? new Date().getTime();
    switch (timeUnit) {
        case "year":
            multipleBy = 365 * 24;
            break;
        case "week":
            multipleBy = 7 * 24;
            break;
        case "day":
            multipleBy = 24;
            break;
        case "hour":
            break;
    }

    return new Date(now - timeThreshold * multipleBy * 60 * 60 * 1000);
}

/**
 *  Get trigger/action form schema
 */
export function getTriggerActionFormSchema(
    currentFormValues: AutomationRuleFormValues,
    profileFields?: ProfileField[],
    automationRulesCatalog?: IAutomationRulesCatalog,
    dynamicSchema?: {
        data: IAutomationRuleAction | undefined;
        isFetching: boolean;
    },
): JsonSchema {
    const profileFieldsSchema = profileFields && mapProfileFieldsToSchemaForFilterForm(profileFields);

    // some custom description for multiselect dropdowns
    const multiSelectProfileFields = profileFields?.filter((field) => field.formType === "tokens");
    if (multiSelectProfileFields?.length) {
        multiSelectProfileFields.forEach((field) => {
            if (profileFieldsSchema?.properties?.[field.apiName]) {
                profileFieldsSchema.properties[field.apiName]["x-control"] = {
                    ...profileFieldsSchema.properties[field.apiName]["x-control"],
                    description: t("Multi-select fields will trigger if the user meets ANY of the criteria."),
                };
            }
        });
    }

    const triggerDropdownOptions = Object.keys(automationRulesCatalog?.triggers ?? {})
        .map((trigger) => {
            return {
                value: trigger,
                label: automationRulesCatalog?.triggers[trigger].name,
                group:
                    automationRulesCatalog?.triggers[trigger].contentType === "users"
                        ? t("User Management")
                        : t("Post Management"),
            };
        })
        .sort((a, b) => compare(a.label, b.label));

    const perTriggerActions = Object.keys(automationRulesCatalog?.actions ?? {}).filter((actionType) =>
        currentFormValues?.trigger?.triggerType
            ? automationRulesCatalog?.triggers[currentFormValues?.trigger.triggerType]?.triggerActions.includes(
                  actionType as AutomationRuleActionType,
              )
            : true,
    );

    const actionDropdownOptions = perTriggerActions.length
        ? Object.values(perTriggerActions ?? {})
              .map((action) => {
                  return {
                      value: action,
                      label: automationRulesCatalog?.actions[action]?.name,
                      group:
                          automationRulesCatalog?.actions[action]?.contentType === "users"
                              ? t("User Management")
                              : t("Post Management"),
                  };
              })
              .sort((a, b) => compare(a.label, b.label))
        : [];

    const selectedTriggerType = currentFormValues.trigger?.triggerType;
    const selectedActionType = currentFormValues.action?.actionType;

    const triggerActionSchema = {
        type: "object",
        description: "Trigger and Action Schema",
        properties: {
            trigger: {
                type: "object",
                description: "Trigger Schema",
                "x-control": {
                    label: t("Trigger"),
                    description: "",
                },
                required: ["triggerType"],
                properties: {
                    triggerType: {
                        type: "string",
                        enum: Object.keys(automationRulesCatalog?.triggers ?? {}),
                        "x-control": {
                            description: t("Select the trigger that will cause this rule to run."),
                            label: t("Rule Trigger"),
                            inputType: "dropDown",
                            choices: {
                                staticOptions: triggerDropdownOptions,
                            },
                            multiple: false,
                        },
                    },
                    triggerValue: {
                        type: "object",
                        properties: {
                            ...(currentFormValues.trigger?.triggerType
                                ? automationRulesCatalog?.triggers[currentFormValues.trigger.triggerType]?.schema
                                      ?.properties
                                : {}),
                            ...(currentFormValues.trigger?.triggerValue?.profileField &&
                                Object.keys(
                                    automationRulesCatalog?.triggers[currentFormValues.trigger.triggerType]?.schema
                                        .properties ?? {},
                                ).includes("profileField") &&
                                profileFieldsSchema &&
                                profileFieldsSchema.properties[
                                    currentFormValues.trigger.triggerValue?.profileField
                                ] && {
                                    [`${currentFormValues.trigger.triggerValue?.profileField}`]: {
                                        ...profileFieldsSchema.properties[
                                            currentFormValues.trigger.triggerValue?.profileField
                                        ],
                                        required: true,
                                    },
                                }),
                        },
                    },
                },
            },
            action: {
                type: "object",
                description: "Action Schema",
                "x-control": {
                    label: t("Action"),
                    description: "",
                },
                required: ["actionType"],
                properties: {
                    actionType: {
                        type: "string",
                        enum: Object.keys(automationRulesCatalog?.actions ?? {}),
                        "x-control": {
                            description: t("Select the action that will occur when this rule is triggered."),
                            label: t("Rule Action"),
                            inputType: "dropDown",
                            choices: {
                                staticOptions: actionDropdownOptions,
                            },
                            multiple: false,
                        },
                    },
                    actionValue: {
                        type: "object",
                        properties: {
                            ...(currentFormValues.action?.actionType &&
                                automationRulesCatalog?.actions[currentFormValues.action.actionType]?.schema
                                    ?.properties),
                        },
                    },
                },
            },
        },
    };

    if (selectedTriggerType) {
        const triggerAdditionalSettings =
            automationRulesCatalog?.triggers[selectedTriggerType]?.schema?.properties?.additionalSettings;
        if (triggerAdditionalSettings) {
            triggerActionSchema.properties["additionalSettings"] = {
                type: "object",
                "x-control": {
                    label: t("Additional settings"),
                    description: "",
                },
                properties: {
                    triggerValue: {
                        type: "object",
                        properties: {
                            ...automationRulesCatalog?.triggers[selectedTriggerType]?.schema?.properties
                                ?.additionalSettings,
                        },
                    },
                },
            };
        }

        // required adjustments
        const triggerValueProperties = triggerActionSchema.properties.trigger.properties.triggerValue.properties;
        triggerActionSchema.properties.trigger.properties.triggerValue["required"] = Object.keys(
            triggerValueProperties,
        ).filter((field) => triggerValueProperties[field].required);
    }

    if (selectedActionType) {
        const actionValueProperties = triggerActionSchema.properties.action.properties.actionValue.properties;
        triggerActionSchema.properties.action.properties.actionValue["required"] = Object.keys(
            actionValueProperties,
        ).filter((field) => actionValueProperties[field].required);
    }

    // some actions have dynamic schemas to load depending on selected values, we need to adjust accordingly
    if (dynamicSchema && selectedActionType) {
        const dynamicSchemaProperties = dynamicSchema.isFetching
            ? {
                  loadingPlaceHolder: {
                      type: "null",
                      nullable: true,
                      "x-control": {
                          inputType: "custom",
                          component: () => <LoadingRectangle height={32} />,
                          componentProps: {},
                      },
                  },
              }
            : dynamicSchema.data
            ? { ...dynamicSchema.data?.dynamicSchema?.properties }
            : {};

        return {
            ...triggerActionSchema,
            properties: {
                ...triggerActionSchema.properties,
                action: {
                    ...triggerActionSchema.properties.action,
                    properties: {
                        ...triggerActionSchema.properties.action.properties,
                        actionValue: {
                            ...triggerActionSchema.properties.action.properties.actionValue,
                            properties: {
                                ...triggerActionSchema.properties.action.properties.actionValue.properties,
                                ...dynamicSchemaProperties,
                            },
                            ...(dynamicSchema.data && {
                                required: [
                                    ...triggerActionSchema.properties.action.properties.actionValue["required"],
                                    ...(dynamicSchema.data?.dynamicSchema?.["required"] ?? []),
                                ],
                            }),
                        },
                    },
                },
            },
        };
    }

    return triggerActionSchema;
}

/**
 *  Custom sort function for date column for automation rules table
 */
export const sortDateColumn = (rowA, rowB, id, desc) => {
    const rowATimeStamp = Array.isArray(rowA.values[id].props?.children)
        ? rowA.values[id].props?.children?.[0]?.props?.timestamp
        : rowA.values[id].props?.children?.props?.timestamp;
    const rowBTimeStamp = Array.isArray(rowB.values[id].props?.children)
        ? rowB.values[id].props?.children?.[0]?.props?.timestamp
        : rowB.values[id].props?.children?.props?.timestamp;

    // we might not have a timestamp, mainly for date last run column,
    // so need to check to have the right sorting, empty values always in the end
    const rowAValue = rowATimeStamp ? new Date(rowATimeStamp).getTime() : desc ? -Infinity : Infinity;
    const rowBValue = rowBTimeStamp ? new Date(rowBTimeStamp).getTime() : desc ? -Infinity : Infinity;
    return rowAValue > rowBValue ? 1 : rowBValue > rowAValue ? -1 : 0;
};

/**
 *  Placeholder when loading automation rules table or preview
 */
export function loadingPlaceholder(section?: string, isEcalationRulesMode?: boolean) {
    const classes = automationRulesClasses(isEcalationRulesMode);
    if (section === "addEdit") {
        return (
            <>
                {Array.from({ length: 10 }, (el, i) => {
                    return (
                        <div className={classes.addEditLoader} key={i}>
                            <LoadingRectangle />
                            <LoadingRectangle />
                        </div>
                    );
                })}
            </>
        );
    }

    if (section === "history") {
        return Array.from({ length: 10 }, (el, i) => {
            return (
                <tr className={classes.historyLoader} key={i}>
                    {Array.from({ length: 6 }, (el, i) => {
                        return (
                            <td key={i}>
                                <LoadingRectangle />
                            </td>
                        );
                    })}
                </tr>
            );
        });
    }

    if (section === "preview") {
        return (
            <div className={classes.previewLoader}>
                {Array.from({ length: 12 }, (_, index) => (
                    <div key={index}>
                        <LoadingRectangle />
                        <LoadingRectangle />
                    </div>
                ))}
            </div>
        );
    }

    return Array(30).fill({
        rule: <LoadingRectangle width="300" height={16} />,
        "last updated": <LoadingRectangle width="100" height={16} />,
        "last run": <LoadingRectangle width="100" height={16} />,
        "auto-run": <LoadingRectangle width="100" height={16} />,
        actions: <LoadingRectangle width="100" height={16} />,
    });
}
