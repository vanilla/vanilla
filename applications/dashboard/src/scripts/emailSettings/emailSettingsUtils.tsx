/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { JSONSchemaType } from "@vanilla/json-schema-forms";

import {
    IEmailStyleSettings,
    IEmailOutgoingSettings,
    IEmailNotificationSettings,
    IEmailSettings,
    IEmailDigestSettings,
} from "@dashboard/emailSettings/emailSettingsTypes";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { t } from "@vanilla/i18n";

/**
 *  Get the email settings schemas
 */
export function getEmailSettingsSchemas() {
    const emailStylesSchema: JSONSchemaType<IEmailStyleSettings> = {
        type: "object",
        properties: {
            "emailStyles.format": {
                type: "boolean",
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
                "x-control": {
                    label: t("Enable sending notification emails"),
                    description: t("When enabled, users may choose to receive notifications from the community"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                },
            },
            "emailNotifications.fullPost": {
                type: "boolean",
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
                type: "string",
                nullable: true,
                maxLength: 500,
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
 *  Get the email digest settings schema
 */
export function getDigestSettingsSchemas() {
    const emailDigestSchema: JSONSchemaType<IEmailDigestSettings> = {
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
            "emailDigest.imageEnabled": {
                type: "boolean",
                default: false,
                "x-control": {
                    label: t("Include featured images with posts in email digest"),
                    inputType: "checkBox",
                    labelType: DashboardLabelType.NONE,
                    conditions: [{ field: "emailDigest.enabled", type: "boolean", const: true }],
                },
            },
            "emailDigest.postCount": {
                type: "number",
                minimum: 3,
                maximum: 20,
                step: 1,
                default: 5,
                "x-control": {
                    label: t("Number of posts"),
                    description: t("Maximum number of posts to be included in the email digest."),
                    inputType: "textBox",
                    type: "number",
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
        required: ["emailDigest.dayOfWeek", "emailDigest.postCount"],
    };
    return emailDigestSchema;
}
