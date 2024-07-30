/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    EMPTY_AUTOMATION_RULE_FORM_VALUES,
    getTriggerActionFormSchema,
    mapApiValuesToFormValues,
    mapFormValuesToApiValues,
} from "@dashboard/automationRules/AutomationRules.utils";
import {
    mockAutomationRulesCatalog,
    mockProfileField,
    mockRecipesList,
} from "@dashboard/automationRules/AutomationRules.fixtures";
import { IAutomationRulesCatalog } from "@dashboard/automationRules/AutomationRules.types";

describe("Automation Rules Utility Functions", () => {
    function assertTriggerActionSchema(result: any, mode: string, catalog?: IAutomationRulesCatalog) {
        switch (mode) {
            case "emptySelectionSchema":
                expect(Object.keys(result.properties)).toEqual(["trigger", "action"]);
                expect(result.properties.trigger.properties.triggerType.enum.length).toBe(
                    Object.keys(catalog?.triggers ?? {}).length,
                );
                expect(result.properties.action.properties.actionType.enum.length).toBe(
                    Object.keys(catalog?.actions ?? {}).length,
                );
                expect(result.properties.trigger.properties.triggerValue.properties).toStrictEqual({});
                expect(result.properties.action.properties.actionValue.properties).toStrictEqual({});
                break;
            case "noAdjustmentSchema":
                expect(
                    Object.keys(result.properties.trigger.properties.triggerValue.properties).includes("emailDomain"),
                ).toBe(true);
                break;
            case "profileFieldAdjustmentSchema":
                expect(
                    Object.keys(result.properties.trigger.properties.triggerValue.properties).includes(
                        mockProfileField["apiName"],
                    ),
                ).toBe(true);
                break;
            case "additionalSettingsAdjustmentSchema":
                expect(result.properties.additionalSettings).toBeDefined();
                expect(
                    Object.keys(result.properties.additionalSettings.properties.triggerValue.properties),
                ).toStrictEqual(
                    Object.keys(
                        mockAutomationRulesCatalog.triggers["staleDiscussionTrigger"]?.schema?.properties
                            .additionalSettings ?? {},
                    ),
                );
                break;
        }
    }
    it("getTriggerActionFormSchema()", () => {
        // initial schema, no trigger/action type selected yet
        const emptySelectionSchema = getTriggerActionFormSchema(
            EMPTY_AUTOMATION_RULE_FORM_VALUES,
            undefined,
            mockAutomationRulesCatalog,
        );
        assertTriggerActionSchema(emptySelectionSchema, "emptySelectionSchema", mockAutomationRulesCatalog);

        // no adjustments required for this trigger, should return schema with no adjustments
        const noAdjustmentSchema = getTriggerActionFormSchema(
            {
                trigger: { triggerType: "emailDomainTrigger", triggerValue: {} },
            },
            undefined,
            mockAutomationRulesCatalog,
        );

        assertTriggerActionSchema(noAdjustmentSchema, "noAdjustmentSchema");

        // adjustment for profile field trigger
        const profileFieldAdjustmentSchema = getTriggerActionFormSchema(
            {
                trigger: {
                    triggerType: "profileFieldTrigger",
                    triggerValue: { profileField: mockProfileField["apiName"] },
                },
            },
            [mockProfileField],
            mockAutomationRulesCatalog,
        );
        assertTriggerActionSchema(profileFieldAdjustmentSchema, "profileFieldAdjustmentSchema");

        // adjustment for trigger with additional settings
        const additionalSettingsAdjustmentSchema = getTriggerActionFormSchema(
            {
                trigger: {
                    triggerType: "staleDiscussionTrigger",
                    triggerValue: {},
                },
            },
            undefined,
            mockAutomationRulesCatalog,
        );
        assertTriggerActionSchema(additionalSettingsAdjustmentSchema, "additionalSettingsAdjustmentSchema");
    });

    it("mapApiValuesToFormValues()", () => {
        // profile fields
        const mappedProfileFieldValue = mapApiValuesToFormValues(mockRecipesList[2]);
        const expectedProfileFieldName = Object.keys(mockRecipesList[2].trigger.triggerValue.profileField)[0];
        expect(mappedProfileFieldValue.trigger?.triggerValue["profileField"]).toBe(expectedProfileFieldName);
        expect(mappedProfileFieldValue.trigger?.triggerValue[expectedProfileFieldName]).toBe(
            mockRecipesList[2].trigger.triggerValue.profileField[expectedProfileFieldName],
        );

        // additional settings
        const mappedAdditionalSettingsValue = mapApiValuesToFormValues(mockRecipesList[1], mockAutomationRulesCatalog);
        expect(Object.keys(mappedAdditionalSettingsValue.additionalSettings ?? {}).length).toBeGreaterThan(0);
        Object.keys(mappedAdditionalSettingsValue.additionalSettings?.triggerValue ?? {}).forEach((key) => {
            expect(mappedAdditionalSettingsValue.additionalSettings?.triggerValue?.[key]).toStrictEqual(
                mockRecipesList[1].trigger.triggerValue[key],
            );
        });
    });

    it("mapFormValuesToApiValues()", () => {
        // profile fields
        const mappedProfileFieldValue = mapFormValuesToApiValues({
            trigger: {
                triggerValue: { profileField: "testProfileField", testProfileField: "someValue" },
                triggerType: "profileFieldTrigger",
            },
        });
        expect(mappedProfileFieldValue.trigger?.triggerValue.profileField).toStrictEqual({
            testProfileField: "someValue",
        });

        // additional settings, we should override the triggerValue with the additionalSettings value
        const mappedAdditionalSettingsValue = mapFormValuesToApiValues({
            trigger: {
                triggerValue: {
                    applyToNewContentOnly: true,
                    triggerTimeLookBackLimit: {
                        length: 5,
                        unit: "days",
                    },
                },
                triggerType: "staleDiscussionTrigger",
            },
            additionalSettings: {
                triggerValue: {
                    applyToNewContentOnly: false,
                    triggerTimeLookBackLimit: {
                        length: 7,
                        unit: "hours",
                    },
                },
            },
        });
        expect(mappedAdditionalSettingsValue.trigger?.triggerValue.applyToNewContentOnly).toBe(false);
        expect(mappedAdditionalSettingsValue.trigger?.triggerValue.triggerTimeLookBackLimit).toStrictEqual({
            length: 7,
            unit: "hours",
        });
    });
});
