/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// All available notification resource API fields.
export interface INotification extends INotificationWritable, INotificationServerManaged {}

// Valid fields for a patch request.
export interface INotificationWritable {
    read: boolean;
}

// Fields maintained by the site.
interface INotificationServerManaged {
    notificationID: number;
    body: string;
    photoUrl: string | null;
    activityName?: string;
    url: string;
    dateInserted: string;
    dateUpdated: string;
}
