/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export interface IDraft {
    draftID: number;
    recordType: string;
    recordID?: number | null;
    parentRecordID: number | null;
    attributes: any;
    insertUserID: number;
    dateInserted: string;
    updateUserID: number;
    dateUpdated: string;
}
