/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface INotification {
    notificationID: number;
    body: string;
    photoUrl: string | null;
    url: string;
    dateInserted: string;
    dateUpdated: string;
    read: boolean;
}
