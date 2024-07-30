/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { getMeta, t } from "@library/utility/appUtils";
import set from "lodash-es/set";
import { JsonSchema } from "packages/vanilla-json-schema-forms/src";
import {
    AISuggestionSourceData,
    AISuggestionsSettings,
    AISuggestionsSettingsForm,
} from "@dashboard/aiSuggestions/AISuggestions.types";
import { ManageSourcesInput } from "@dashboard/aiSuggestions/components/ManageSourcesInput";

export interface AISuggestionSectionSchema {
    title: string;
    schema: JsonSchema;
}

/**
 * Get the schema for the settings form using the saved settings as default values divided into sections
 */
export function getSettingsSchema(settings?: AISuggestionsSettings): AISuggestionSectionSchema[] | undefined {
    if (!settings) {
        return undefined;
    }
    // eslint-disable-next-line
    console.log(settings);

    const suggestionSources: Record<string, AISuggestionSourceData> = getMeta("suggestionSources", {});

    const enabledSources = settings.sources
        ? Object.entries(settings.sources)
              .filter(([_, { enabled }]) => enabled)
              .map(([sourceID]) => sourceID)
        : ["category"];

    const sourceOptions: Record<string, string> = {};
    const exclusionFields: Record<string, any> = {};

    Object.entries(suggestionSources).forEach(([sourceID, sourceData]) => {
        sourceOptions[sourceID] = t(sourceData.enabledLabel);
        if (sourceData.exclusionChoices && sourceData.exclusionLabel) {
            const sourceSettings = settings.sources?.[sourceID];

            exclusionFields[sourceID] = {
                type: "array",
                default: sourceSettings ? sourceSettings.exclusionIDs ?? [] : [],
                "x-control": {
                    inputType: "tokens",
                    label: t(sourceData.exclusionLabel),
                    choices: sourceData.exclusionChoices,
                    conditions: [
                        {
                            field: "sources.enabled",
                            type: "array",
                            contains: {
                                type: "string",
                                const: sourceID,
                            },
                        },
                    ],
                },
            };
        }
    });

    return [
        {
            title: t("Customize AI Persona"),
            schema: {
                type: "object",
                properties: {
                    name: {
                        type: "string",
                        default: settings.name ?? "",
                        "x-control": {
                            label: t("Assistant Name"),
                            description: t("This name will be shown in the front-facing community."),
                            inputType: "textBox",
                        },
                    },
                    icon: {
                        type: "string",
                        nullable: true,
                        maxLength: 500,
                        default: settings.icon ?? "",
                        "x-control": {
                            label: t("Assistant Icon"),
                            description: t(
                                "Recommended dimensions are 400px by 300px or smaller with a similar ratio.",
                            ),
                            inputType: "upload",
                        },
                    },
                },
                required: [],
            },
        },
        {
            title: t("Language Style"),
            schema: {
                type: "object",
                properties: {
                    useBrEnglish: {
                        type: "boolean",
                        default: settings.useBrEnglish ?? false,
                        "x-control": {
                            label: t("Use British English Spelling"),
                            inputType: "checkBox",
                            labelType: DashboardLabelType.NONE,
                            labelBold: false,
                        },
                    },
                    toneOfVoice: {
                        type: "string",
                        default: settings.toneOfVoice ?? "friendly",
                        "x-control": {
                            label: t("Assistant Tone of Voice"),
                            description: t("This controls how the responses are phrased and spelled."),
                            inputType: "radio",
                            choices: {
                                staticOptions: {
                                    friendly: t("Friendly and Personal"),
                                    professional: t("Professional"),
                                    technical: t("Technical"),
                                },
                            },
                            notesPerOption: {
                                friendly: t(
                                    "Hello there! I am your AI Suggestion Assistant, here to help you find the best articles and posts to answer your questions. Feel free to ask anything, I am here to make your search fun and informative!",
                                ),
                                professional: t(
                                    "Greetings. I am your AI Suggestion Assistant, here to assist you in finding relevant articles and posts to answer your queries. Please feel free to ask any questions you may have.",
                                ),
                                technical: t(
                                    "Welcome. I am your AI Suggestion Assistant, a machine learning-based tool designed to locate and suggest relevant articles and posts in response to your queries. Please input your questions and I will provide the most suitable information.",
                                ),
                            },
                        },
                    },
                    levelOfTech: {
                        type: "string",
                        default: settings.levelOfTech ?? "layman",
                        "x-control": {
                            label: t("Level of Technical Language"),
                            inputType: "radio",
                            choices: {
                                staticOptions: {
                                    layman: t("Layman's Terms"),
                                    intermediate: t("Intermediate"),
                                    balanced: t("Balanced"),
                                    advanced: t("Advanced"),
                                    technical: t("Technical Jargon"),
                                },
                            },
                            notesPerOption: {
                                layman: t(
                                    "Uses simple, everyday language that anyone can understand, regardless of their background or expertise.",
                                ),
                                intermediate: t(
                                    "More complex language and may introduce some industry-specific terms, but still understandable to most people.",
                                ),
                                balanced: t(
                                    "Uses industry-specific terms where necessary, but also provides explanations of definitions to ensure clarity.",
                                ),
                                advanced: t(
                                    "More technical language and industry-specific terms -- assumes a higher levelOfTech of understanding/familiarity with subject matter.",
                                ),
                                technical: t(
                                    "Uses highly technical language and industry-specific terms intended for those with a deep understanding of subject matter.",
                                ),
                            },
                        },
                    },
                },
                required: [],
            },
        },
        {
            title: t("Manage Suggested Answer Sources"),
            schema: {
                type: "object",
                properties: {
                    sources: {
                        type: "object",
                        properties: {
                            enabled: {
                                default: enabledSources,
                                type: "array",
                                items: {
                                    type: "string",
                                    enum: Object.keys(sourceOptions),
                                },
                                "x-control": {
                                    labelType: DashboardLabelType.VERTICAL,
                                    inputType: "custom",
                                    component: ManageSourcesInput,
                                    componentProps: {
                                        options: sourceOptions,
                                    },
                                },
                            },
                            exclusions: {
                                type: "object",
                                properties: exclusionFields,
                                required: [],
                            },
                        },
                        required: [],
                    },
                },
                required: [],
            },
        },
    ];
}

/**
 * Get the initial form values for the settings page from the schema
 */
export function getInitialSettings(sections: AISuggestionSectionSchema[]): AISuggestionsSettingsForm {
    const initialSettings: any = {};

    sections.forEach(({ schema }) => {
        Object.entries(schema.properties).forEach(([key, { default: defaultValue, properties }]) => {
            if (key === "sources") {
                // the sources section needs to be handled differently
                const { enabled, exclusions } = properties;
                set(initialSettings, "sources.enabled", enabled.default);
                Object.entries(exclusions.properties).forEach(([sourceID, sourceField]: [string, any]) => {
                    set(initialSettings, `sources.exclusions.${sourceID}`, sourceField.default);
                });
            } else {
                set(initialSettings, key, defaultValue);
            }
        });
    });

    return initialSettings as AISuggestionsSettingsForm;
}
