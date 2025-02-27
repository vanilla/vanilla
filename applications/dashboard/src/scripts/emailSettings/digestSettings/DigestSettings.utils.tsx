/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import DigestOptInExistingUsers from "@dashboard/emailSettings/components/DigestOptInExistingUsers";
import MonthlyRecurrenceInput from "@dashboard/emailSettings/components/MonthlyRecurrenceInput";
import {
    IEmailDigestAdditionalSetting,
    IEmailDigestAdditionalSettingPosition,
    IEmailDigestSettingsConfigValues,
    IEmailDigestSettingsFormValues,
    RecurrenceFrequency,
    RecurrenceSetPosition,
} from "@dashboard/emailSettings/EmailSettings.types";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { t } from "@vanilla/i18n";
import { JSONSchemaType } from "@vanilla/json-schema-forms";

const emptyRichEditorValue = EMPTY_RICH2_BODY;

/* Digest Setting Schemas*/

/**
 *  Get the email digest settings schemas
 *  @param additionalSettings This will allow external applications (e.g. groups) to add additional properties to the schema
 */
export function getDigestSettingsSchemas(additionalSettings?: IEmailDigestAdditionalSetting) {
    const additionalSettingsRequiredProperties = Object.keys(
        additionalSettings?.[IEmailDigestAdditionalSettingPosition.AFTER_POST_COUNT] ?? {},
    ).reduce((acc, setting) => {
        const required =
            additionalSettings?.[IEmailDigestAdditionalSettingPosition.AFTER_POST_COUNT]?.[setting]?.required &&
            setting;
        return required ? [...acc, required] : acc;
    }, []);

    const emailDigestGeneralSchema: JSONSchemaType<IEmailDigestSettingsFormValues> = {
        type: "object",
        properties: {
            "emailDigest.enabled": {
                type: "boolean",
                default: false,
                "x-control": {
                    label: t("Use community email digest"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                },
            },
        },
        required: [],
    };

    const emailDigestDeliverySchema: JSONSchemaType<IEmailDigestSettingsFormValues> = {
        type: "object",
        properties: {
            "emailDigest.defaultFrequency": {
                type: ["string"],
                default: "weekly",
                "x-control": {
                    label: t("Default Frequency"),
                    description: t(
                        "New subscribers will receive the Digest at the set frequency by default. Users can configure their preferred frequency in their notification preferences.",
                    ),
                    inputType: "select",
                    options: [
                        { value: RecurrenceFrequency.DAILY, label: t("Daily") },
                        { value: RecurrenceFrequency.WEEKLY, label: t("Weekly") },
                        { value: RecurrenceFrequency.MONTHLY, label: t("Monthly") },
                    ],
                },
            },
            // this is a legacy config name (before we offered daily or monthly digests), so unfortunately we can't add "weekly" to its namespace.
            "emailDigest.dayOfWeek": {
                type: ["string", "number"],
                default: 1,
                "x-control": {
                    label: t("Weekly Delivery Day"),
                    description: t("The Digest will be delivered on the set day of the week."),
                    inputType: "select",
                    options: [
                        { value: 1, label: t("weekday.long.1", "Monday") },
                        { value: 2, label: t("weekday.long.2", "Tuesday") },
                        { value: 3, label: t("weekday.long.3", "Wednesday") },
                        { value: 4, label: t("weekday.long.4", "Thursday") },
                        { value: 5, label: t("weekday.long.5", "Friday") },
                        { value: 6, label: t("weekday.long.6", "Saturday") },
                        { value: 7, label: t("weekday.long.7", "Sunday") },
                    ],
                },
            },

            "emailDigest.monthly": {
                type: "object",
                required: ["setPosition", "dayOfWeek"],
                "x-control": {
                    legend: t("Monthly Delivery Day"),
                    description: t("The Digest will be delivered on the set day, once per month."),
                    inputType: "custom",
                    component: MonthlyRecurrenceInput,
                },
                properties: {
                    setPosition: {
                        type: "string",
                        default: RecurrenceSetPosition.FIRST,
                    },
                    dayOfWeek: {
                        type: "number",
                        default: 1,
                    },
                },
            },
        },
        required: ["emailDigest.defaultFrequency", "emailDigest.monthly", "emailDigest.dayOfWeek"],
    };

    const emailDigestContentSchema: JSONSchemaType<IEmailDigestSettingsFormValues> = {
        type: "object",
        properties: {
            "emailDigest.logo": {
                type: "string",
                nullable: true,
                maxLength: 500,
                "x-control": {
                    label: t("Email Digest Logo"),
                    description:
                        t("If left empty, Email Logo is used.") +
                        t(" Recommended dimensions are about 400px by 300px or smaller with similar ratio."),
                    inputType: "upload",
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.postCount": {
                type: "number",
                minimum: 3,
                maximum: 20,
                default: 5,
                errorMessage: [
                    {
                        keyword: "minimum",
                        message: t("Post number must be between 3–20."),
                    },
                    {
                        keyword: "maximum",
                        message: t("Post number must be between 3–20."),
                    },
                    {
                        keyword: "required",
                        message: t("Required field."),
                    },
                ],
                "x-control": {
                    label: t("Number of posts"),
                    description: t("Maximum number of posts to be included in the email digest."),
                    inputType: "textBox",
                    type: "number",
                },
            },
            ...(additionalSettings?.[IEmailDigestAdditionalSettingPosition.AFTER_POST_COUNT] ?? {}),

            // this one serves only for the UI, it's not used in the backend and only rendersa subtitle for metas section
            "emailDigest.metaOptions": {
                type: "object",
                "x-control": {
                    inputType: "empty",
                    label: t("Meta Options"),
                    noBorder: true,
                },
                properties: {},
            },

            "emailDigest.imageEnabled": {
                type: "boolean",
                default: false,
                "x-control": {
                    label: t("Include Featured Images"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    noBorder: true,
                    isNested: true,
                },
            },
            "emailDigest.authorEnabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("Author"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    noBorder: true,
                    isNested: true,
                },
            },
            "emailDigest.viewCountEnabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("View Count"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    noBorder: true,
                    isNested: true,
                },
            },
            "emailDigest.commentCountEnabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("Comment Count"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    noBorder: true,
                    isNested: true,
                },
            },
            "emailDigest.scoreCountEnabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("Score Count"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    isNested: true,
                },
            },
            "emailDigest.title": {
                type: "string",
                minLength: 1,
                maxLength: 60,
                errorMessage: [
                    {
                        keyword: "required",
                        message: t("Required field."),
                    },
                    {
                        keyword: "maxLength",
                        message: t("Maximum length is 60 characters."),
                    },
                ],
                "x-control": {
                    label: t("Subject Line and Title"),
                    description: t("Limits: 60 characters, no breaks."),
                    labelType: DashboardLabelType.VERTICAL,
                    type: "textarea",
                    inputType: "textBox",
                    placeholder: t("This week's trending content"),
                    noBorder: true,
                },
            },
            "emailDigest.includeCommunityName": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t(
                        "Start the digest subject line with the [Banner Title] set in Appearance > Branding & SEO",
                    ),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    isNested: true,
                },
            },
            "emailDigest.introduction": {
                type: ["string", "array"],
                default: emptyRichEditorValue,
                "x-control": {
                    label: t("Introduction"),
                    labelType: DashboardLabelType.VERTICAL,
                    description: t("The first line of content in the email digest after the title."),
                    inputType: "richeditor",
                },
            },
            "emailDigest.footer": {
                type: ["string", "array"],
                default: emptyRichEditorValue,
                "x-control": {
                    label: t("Footer"),
                    labelType: DashboardLabelType.VERTICAL,
                    description: t(
                        "This may be used to include content such as organization name and address in the email digest.",
                    ),
                    inputType: "richeditor",
                },
            },
        },
        required: ["emailDigest.postCount", "emailDigest.title", ...additionalSettingsRequiredProperties],
    };

    const emailDigestSubscriptionSchema: JSONSchemaType<IEmailDigestSettingsFormValues> = {
        type: "object",
        properties: {
            "emailDigest.autosubscribe.enabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("Auto Subscribe to Digest"),
                    description: t("Set all new users to opt-in to digest by default."),
                    inputType: "toggle",
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },

            // Show a modal for users to backdate autosubscribe for existing users
            "emailDigest.optInTimeFrame": {
                "x-control": {
                    label: t("Opt-in Existing Users to Digest"),
                    labelType: DashboardLabelType.JUSTIFIED,
                    description: (
                        <>{t("Set a backdate based on last log in and opt-in those users to receiving the Digest.")}</>
                    ),
                    inputType: "custom",
                    component: DigestOptInExistingUsers,
                    conditions: [
                        { field: "emailDigest.enabled", type: "boolean", const: true },
                        { field: "emailDigest.autosubscribe.enabled", type: "boolean", const: true },
                    ],
                },
            },
        },
        required: [],
    };

    const emailDigestSchema: JSONSchemaType<IEmailDigestSettingsFormValues> = {
        type: "object",
        properties: {
            ...emailDigestGeneralSchema.properties,
            ...emailDigestDeliverySchema.properties,
            ...emailDigestContentSchema.properties,
            ...emailDigestSubscriptionSchema.properties,
        },
        required: [
            ...emailDigestGeneralSchema.required,
            ...emailDigestDeliverySchema.required,
            ...emailDigestContentSchema.required,
            ...emailDigestSubscriptionSchema.required,
        ],
    };
    return {
        emailDigestSchema: emailDigestSchema,
        emailDigestGeneralSchema: emailDigestGeneralSchema,
        emailDigestDeliverySchema: emailDigestDeliverySchema,
        emailDigestContentSchema: emailDigestContentSchema,
        emailDigestSubscriptionSchema: emailDigestSubscriptionSchema,
    };
}

export function mapFormValuesToConfigValues(values: IEmailDigestSettingsFormValues): IEmailDigestSettingsConfigValues {
    let {
        "emailDigest.metaOptions": deletedProperty,
        "emailDigest.monthly": { dayOfWeek, setPosition },
        ...rest
    } = values;

    return {
        ...rest,
        "emailDigest.monthly.dayOfWeek": dayOfWeek,
        "emailDigest.monthly.setPosition": setPosition,
        ...["emailDigest.introduction", "emailDigest.footer"].reduce((acc, key) => {
            if (typeof rest?.[key] !== "string") {
                let stringVal = "";
                try {
                    stringVal = JSON.stringify(rest[key]);
                } catch (e) {
                    stringVal = "";
                }
                return { ...acc, [key]: stringVal };
            }
            return { ...acc, [key]: rest[key] };
        }, {}),
    };
}

export function getInitialFormValues(
    configValues: IEmailDigestSettingsConfigValues,
    defaultValues: IEmailDigestSettingsFormValues,
): IEmailDigestSettingsFormValues {
    let {
        "emailDigest.monthly.dayOfWeek": monthlyDayOfWeek,
        "emailDigest.monthly.setPosition": monthlySetPosition,
        ...rest
    } = configValues;

    return {
        ...rest,
        "emailDigest.monthly": {
            dayOfWeek: monthlyDayOfWeek,
            setPosition: monthlySetPosition,
        },
        ...["emailDigest.introduction", "emailDigest.footer"].reduce((acc, key) => {
            if (typeof configValues?.[key] === "string") {
                try {
                    return { ...acc, [key]: JSON.parse(configValues[key]) };
                } catch (e) {
                    return { ...acc, [key]: defaultValues[key] };
                }
            }
            return { ...acc, [key]: configValues[key] };
        }, {}),
    };
}
