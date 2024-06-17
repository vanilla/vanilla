/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    AddEditAutomationRuleParams,
    AutomationRuleActionType,
    AutomationRuleFormValues,
    IAutomationRule,
    IAutomationRuleDispatch,
    IAutomationRulesCatalog,
} from "@dashboard/automationRules/AutomationRules.types";
import mapProfileFieldsToSchemaForFilterForm from "@dashboard/components/panels/mapProfileFieldsToSchemaForFilterForm";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { t } from "@vanilla/i18n";
import { JsonSchema } from "@vanilla/json-schema-forms";
import cloneDeep from "lodash/cloneDeep";

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
 *  Mainly adjustments for profile field form fields
 */
export function mapApiValuesToFormValues(recipe?: IAutomationRule | IAutomationRuleDispatch): AutomationRuleFormValues {
    if (recipe) {
        const triggerValue = recipe.trigger.triggerValue?.profileField
            ? {
                  ...recipe.trigger.triggerValue,
                  profileField: Object.keys(recipe.trigger.triggerValue?.profileField)[0],
                  ...recipe.trigger.triggerValue?.profileField,
              }
            : recipe.trigger.triggerValue;

        return {
            trigger: {
                triggerType: recipe.trigger.triggerType,
                triggerValue: triggerValue,
            },
            action: { ...recipe.action },
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

    const triggerActionSchema = {
        type: "object",
        description: "Trigger and Action Schema",
        properties: {
            trigger: {
                type: "object",
                description: "Trigger Schema",
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
                                staticOptions: Object.fromEntries(
                                    Object.keys(automationRulesCatalog?.triggers ?? {}).map((trigger) => [
                                        trigger,
                                        automationRulesCatalog?.triggers[trigger].name,
                                    ]),
                                ),
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
                                    [`${currentFormValues.trigger.triggerValue?.profileField}`]:
                                        profileFieldsSchema.properties[
                                            currentFormValues.trigger.triggerValue?.profileField
                                        ],
                                }),
                        },
                    },
                },
            },
            action: {
                type: "object",
                description: "Action Schema",
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
                                staticOptions: Object.fromEntries(
                                    Object.keys(automationRulesCatalog?.actions ?? {})
                                        .filter((actionType) =>
                                            currentFormValues?.trigger?.triggerType
                                                ? automationRulesCatalog?.triggers[
                                                      currentFormValues?.trigger.triggerType
                                                  ].triggerActions.includes(actionType as AutomationRuleActionType)
                                                : true,
                                        )
                                        .map((action) => [action, automationRulesCatalog?.actions[action].name]),
                                ),
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

    if (currentFormValues.trigger?.triggerType) {
        let triggerRequiredKeys = Object.keys(
            triggerActionSchema.properties.trigger.properties.triggerValue.properties,
        );
        // bit of adjustments for time based triggers
        if (
            [
                "staleDiscussionTrigger",
                "staleCollectionTrigger",
                "lastActiveDiscussionTrigger",
                "timeSinceUserRegistrationTrigger",
            ].includes(currentFormValues.trigger.triggerType)
        ) {
            triggerRequiredKeys = triggerRequiredKeys.filter((key) => key !== "maxTimeThreshold");
            if (
                !currentFormValues.trigger?.triggerValue?.maxTimeThreshold ||
                currentFormValues.trigger?.triggerValue?.maxTimeThreshold === "" ||
                !currentFormValues.trigger?.triggerValue?.maxTimeThreshold.toString().match(/^\d+$/)
            ) {
                triggerRequiredKeys = triggerRequiredKeys.filter((key) => key !== "maxTimeUnit");
            }
        }
        triggerActionSchema.properties.trigger.properties.triggerValue["required"] = triggerRequiredKeys;
    }

    if (currentFormValues.action?.actionType) {
        const actionRequiredKeys = Object.keys(triggerActionSchema.properties.action.properties.actionValue.properties);
        triggerActionSchema.properties.action.properties.actionValue["required"] = actionRequiredKeys.filter(
            (key) => key !== "removeRoleID",
        );
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
 *  Placeholder when loading automation rules table or add/edit page
 */
export function loadingPlaceholder(section?: string) {
    if (section === "addEdit") {
        return (
            <>
                {Array.from({ length: 10 }, (el, i) => {
                    return (
                        <div style={{ display: "flex", justifyContent: "space-between" }} key={i}>
                            <LoadingRectangle style={{ width: "27%", height: 24, marginTop: 16 }} />
                            <LoadingRectangle style={{ width: "70%", height: 24, marginTop: 16 }} />
                        </div>
                    );
                })}
            </>
        );
    }
    if (section === "history") {
        return Array.from({ length: 10 }, (el, i) => {
            return (
                <tr key={i}>
                    <td>
                        <LoadingRectangle style={{ width: 300, height: 16, marginTop: 16 }} />
                    </td>
                    <td>
                        <LoadingRectangle style={{ width: 100, height: 16, marginTop: 16 }} />
                    </td>
                    <td>
                        <LoadingRectangle style={{ width: 100, height: 16, marginTop: 16 }} />
                    </td>
                    <td>
                        <LoadingRectangle style={{ width: 100, height: 16, marginTop: 16 }} />
                    </td>
                    <td>
                        <LoadingRectangle style={{ width: 100, height: 16, marginTop: 16 }} />
                    </td>
                    <td>
                        <LoadingRectangle style={{ width: 100, height: 16, marginTop: 16 }} />
                    </td>
                </tr>
            );
        });
    }
    return Array(30).fill({
        rule: <LoadingRectangle width="300" height={16} />,
        "last updated": <LoadingRectangle width="100" height={16} />,
        "last run": <LoadingRectangle width="100" height={16} />,
        "auto-run": <LoadingRectangle width="100" height={16} />,
        actions: <LoadingRectangle width="100" height={16} />,
    });
}
