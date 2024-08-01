/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

/** Digest types */
export interface IEmailDigestSettings {
    "emailDigest.enabled": boolean;
    "emailDigest.logo": string;
    "emailDigest.dayOfWeek": number;
    "emailDigest.postCount": number;
    "emailDigest.title": string;
    "emailDigest.includeCommunityName": boolean;
    "emailDigest.introduction": string;
    "emailDigest.footer": string;
    "emailDigest.metaOptions": object; // this one serves only for the UI, it's not used in the backend and only renders a title header for metas section
    "emailDigest.imageEnabled": boolean;
    "emailDigest.authorEnabled": boolean;
    "emailDigest.viewCountEnabled": boolean;
    "emailDigest.commentCountEnabled": boolean;
    "emailDigest.scoreCountEnabled": boolean;
}

export interface ISentDigest {
    dateScheduled: string;
    totalSubscribers: number;
}

export interface IEmailDigestDeliveryDates {
    sent: ISentDigest[];
    scheduled: string;
    upcoming: string[];
}

/** Notification Types */

import { MyValue } from "@library/vanilla-editor/typescript";

export interface IEmailStyleSettings {
    "emailStyles.format": boolean;
    "emailStyles.image": string;
    "emailStyles.textColor": string;
    "emailStyles.backgroundColor": string;
    "emailStyles.containerBackgroundColor": string;
    "emailStyles.buttonTextColor": string;
    "emailStyles.buttonBackgroundColor": string;
}

export interface IEmailOutgoingSettings {
    "outgoingEmails.supportName": string;
    "outgoingEmails.supportAddress": string;
}

export interface IEmailNotificationSettings {
    "emailNotifications.disabled": boolean;
    "emailNotifications.fullPost": boolean;
    "outgoingEmails.footer": MyValue;
}

export type IEmailSettings = IEmailStyleSettings | IEmailOutgoingSettings | IEmailNotificationSettings;

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
