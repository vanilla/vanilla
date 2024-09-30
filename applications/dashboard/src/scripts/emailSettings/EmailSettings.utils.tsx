/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { JSONSchemaType } from "@vanilla/json-schema-forms";
import {
    IEmailStyleSettings,
    IEmailOutgoingSettings,
    IEmailNotificationSettings,
    IEmailSettings,
    IEmailDigestSettings,
} from "@dashboard/emailSettings/EmailSettings.types";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { t } from "@vanilla/i18n";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";

const emptyRichEditorValue = EMPTY_RICH2_BODY;
/**
 *  Get the email settings schemas
 */
export function getEmailSettingsSchemas() {
    const emailStylesSchema: JSONSchemaType<IEmailStyleSettings> = {
        type: "object",
        properties: {
            "emailStyles.format": {
                type: "boolean",
                default: false,
                "x-control": {
                    label: t("Enable HTML emails"),
                    description: t("Spruce up your emails by adding a logo and customizing the colors."),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                },
            },
            "emailStyles.image": {
                type: "string",
                nullable: true,
                maxLength: 500,
                default: "",
                "x-control": {
                    label: t("Email Logo"),
                    description: t("Recommended dimensions are about 400px by 300px or smaller with similar ratio."),
                    inputType: "upload",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.textColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                default: "",
                "x-control": {
                    label: t("Text Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.backgroundColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                default: "",
                "x-control": {
                    label: t("Background Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.containerBackgroundColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                default: "",
                "x-control": {
                    label: t("Page Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.buttonTextColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                default: "",
                "x-control": {
                    label: t("Button Text Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
            "emailStyles.buttonBackgroundColor": {
                type: "string",
                nullable: true,
                maxLength: 9,
                default: "",
                "x-control": {
                    label: t("Button Background Color"),
                    inputType: "color",
                    conditions: [{ field: "emailStyles.format", type: "boolean", const: true }],
                },
            },
        },
    };

    const outgoingEmailSchema: JSONSchemaType<IEmailOutgoingSettings> = {
        type: "object",
        properties: {
            "outgoingEmails.supportName": {
                type: "string",
                nullable: true,
                minLength: 1,
                default: "",
                "x-control": {
                    label: t("From Name"),
                    description: t("Email sent from the application will be addressed from this name"),
                    inputType: "textBox",
                    default: "Default From Name",
                },
            },
            "outgoingEmails.supportAddress": {
                type: "string",
                nullable: true,
                minLength: 1,
                default: "",
                "x-control": {
                    label: t("From Email Address"),
                    description: t("Email sent from the application will be addressed from this email address"),
                    inputType: "textBox",
                    default: "Default Email Address",
                },
            },
        },
        required: ["outgoingEmails.supportName", "outgoingEmails.supportAddress"],
    };

    const emailNotificationsSchema: JSONSchemaType<IEmailNotificationSettings> = {
        type: "object",
        properties: {
            "emailNotifications.disabled": {
                type: "boolean",
                default: false,
                "x-control": {
                    label: t("Enable sending notification emails"),
                    description: t("When enabled, users may choose to receive notifications from the community"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                },
            },
            "emailNotifications.fullPost": {
                type: "boolean",
                default: false,
                "x-control": {
                    label: t("Include full post in email notifications"),
                    description: t(
                        "If enabled, the full content of posts will be sent in email notifications to users.",
                    ),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    conditions: [{ field: "emailNotifications.disabled", type: "boolean", const: true }],
                },
            },
            "outgoingEmails.footer": {
                type: ["string", "array"],
                maxLength: 500,
                default: emptyRichEditorValue,
                "x-control": {
                    label: t("Email Footer"),
                    description: t(
                        "This may be used to include content such as organization name and address in all outgoing notification emails.",
                    ),
                    inputType: "richeditor",
                },
            },
        },
    };

    const emailSettingsSchema: JSONSchemaType<IEmailSettings> = {
        type: "object",
        properties: {
            ...emailStylesSchema.properties,
            ...outgoingEmailSchema.properties,
            ...emailNotificationsSchema.properties,
        },
    };
    return {
        emailSettingsSchema: emailSettingsSchema,
        emailNotificationsSchema: emailNotificationsSchema,
        emailStylesSchema: emailStylesSchema,
        outgoingEmailSchema: outgoingEmailSchema,
    };
}

/* Digest Setting Schemas*/

/**
 *  Get the email digest settings schemas
 */
export function getDigestSettingsSchemas() {
    const emailDigestGeneralSchema: JSONSchemaType<IEmailDigestSettings> = {
        type: "object",
        properties: {
            "emailDigest.enabled": {
                type: "boolean",
                default: false,
                "x-control": {
                    label: t("Use weekly community email digest"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                },
            },
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
            "emailDigest.dayOfWeek": {
                type: ["string", "number"],
                default: 1,
                "x-control": {
                    label: t("Delivery Date"),
                    description: `
                    ${t(
                        "Email digests will be sent on the selected day every week. Changes to the set day will take affect the following week.",
                    )}
                    <br/>
                    <a href="https://success.vanillaforums.com/kb/articles/1479-email-digest" target="_blank">
                        ${t("More information")}
                    </a>
                `,
                    inputType: "dropDown",
                    choices: {
                        staticOptions: {
                            1: t("weekday.long.1", "Monday"),
                            2: t("weekday.long.2", "Tuesday"),
                            3: t("weekday.long.3", "Wednesday"),
                            4: t("weekday.long.4", "Thursday"),
                            5: t("weekday.long.5", "Friday"),
                            6: t("weekday.long.6", "Saturday"),
                            7: t("weekday.long.7", "Sunday"),
                        },
                    },
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
        },
        required: ["emailDigest.dayOfWeek"],
    };

    const emailDigestContentSchema: JSONSchemaType<IEmailDigestSettings> = {
        type: "object",
        properties: {
            "emailDigest.postCount": {
                type: "number",
                minimum: 3,
                maximum: 20,
                default: 5,
                errorMessage: [
                    {
                        keyword: "minimum",
                        message: t("Out of range. Minimum is 3."),
                    },
                    {
                        keyword: "maximum",
                        message: t("Out of range. Minimum is 20."),
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
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },

            // this one serves only for the UI, it's not used in the backend and only rendersa subtitle for metas section
            "emailDigest.metaOptions": {
                type: "object",
                "x-control": {
                    label: t("Meta Options"),
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
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.authorEnabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("Author"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.viewCountEnabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("View Count"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.commentCountEnabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("Comment Count"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.scoreCountEnabled": {
                type: "boolean",
                default: true,
                "x-control": {
                    label: t("Score Count"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
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
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
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
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.introduction": {
                type: ["string", "array"],
                default: emptyRichEditorValue,
                "x-control": {
                    label: t("Introduction"),
                    description: t("The first line of content in the email digest after the title."),
                    inputType: "richeditor",
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.footer": {
                type: ["string", "array"],
                default: emptyRichEditorValue,
                "x-control": {
                    label: t("Footer"),
                    description: t(
                        "This may be used to include content such as organization name and address in the email digest.",
                    ),
                    inputType: "richeditor",
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
        },
        required: ["emailDigest.postCount", "emailDigest.title"],
    };

    const emailDigestSchema: JSONSchemaType<IEmailDigestSettings> = {
        type: "object",
        properties: {
            ...emailDigestGeneralSchema.properties,
            ...emailDigestContentSchema.properties,
        },
        required: [...emailDigestGeneralSchema.required, ...emailDigestContentSchema.required],
    };
    return {
        emailDigestSchema: emailDigestSchema,
        emailDigestGeneralSchema: emailDigestGeneralSchema,
        emailDigestContentSchema: emailDigestContentSchema,
    };
}
