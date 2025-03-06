/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IEmailNotificationSettings,
    IEmailOutgoingSettings,
    IEmailSettings,
    IEmailStyleSettings,
} from "@dashboard/emailSettings/EmailSettings.types";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { t } from "@vanilla/i18n";
import { JSONSchemaType } from "@vanilla/json-schema-forms";

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
                    labelType: DashboardLabelType.VERTICAL,
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
