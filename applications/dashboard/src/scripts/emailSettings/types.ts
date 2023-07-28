/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MyValue } from "@library/vanilla-editor/typescript";

export interface IEmailStyleSettings {
    "emailStyles.format": boolean;
    "emailStyles.logoUrl": string;
    "emailStyles.textColor": string;
    "emailStyles.backgroundColor": string;
    "emailStyles.containerBackgroundColor": string;
    "emailStyles.buttonTextColor": string;
    "emailStyles.buttonBackgroundColor": string;
}

export interface IEmailOutgoingSettings {
    "outgoingEmails.supportName": string;
    "outgoingEmails.supportAddress": string;
    "outgoingEmails.footer": MyValue;
}

export interface IEmailNotificationSettings {
    "emailNotifications.disabled": boolean;
    "emailNotifications.fullPost": boolean;
}

export interface IEmailDigestSettings {
    "emailDigest.enabled": boolean;
    "emailDigest.imageEnabled": boolean;
    "emailDigest.schedule": number;
}

export type IEmailSettings =
    | (IEmailStyleSettings & IEmailOutgoingSettings & IEmailNotificationSettings)
    | IEmailDigestSettings;

export interface IEmailConfigs extends Omit<IEmailSettings, "emailStyles.format" | "outgoingEmails.footer"> {
    "emailStyles.format": string;
    "outgoingEmails.footer": string;
}

export interface IEmailPreviewPayload {
    emailFormat: string;
    templateStyles: {
        logoUrl: string;
        textColor: string;
        backgroundColor: string;
        containerBackgroundColor: string;
        buttonTextColor: string;
        buttonBackgroundColor: string;
    };
    footer: string;
}

export interface ITestEmailPayload extends IEmailPreviewPayload {
    destinationEmailAddress: string;
    from: {
        supportName: string;
        supportAddress: string;
    };
}
