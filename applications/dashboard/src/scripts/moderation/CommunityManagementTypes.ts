/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IRecordStatus } from "@dashboard/@types/api/discussion";
import { IUserFragment } from "@library/@types/api/users";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";

export interface ICommunityManagementRecord {
    recordType: string;
    recordID: string;
    placeRecordType: string;
    placeRecordID: number;
    recordName: string;
    recordFormat: string;
    recordUrl: string;
    recordIsLive: boolean;
    recordWasEdited: boolean;
    // This will be record contents as of the most recent report.
    recordExcerpt: string;
    recordUserID: number;
    recordUser?: IUserFragment;
    placeRecordUrl: string;
    placeRecordName: string;
    recordDateInserted: string;
    recordDateUpdated: string;
    recordHtml: string;
}

/**
 * GET /api/v2/reports
 * {
 *  status?: string | string[];
 *  reason?: string[] | string;
 *  placeRecordType?: string;
 *  placeRecordID?: number[] | number;
 *  sort?: "dateInserted" | "recordDateInserted" | "-dateInserted" | "+dateInserted"
 *  limit?: number;
 *  page?: number;
 * }
 *
 */
export interface IReport extends ICommunityManagementRecord {
    reportID: number;
    insertUserID: number;
    insertUser?: IUserFragment;
    dateInserted: string;
    dateUpdated: any;
    updateUserID: any;
    status: string;
    noteHtml: string;
    reasons: IReason[];
}

/**
 * POST /api/v2/reports
 */
interface IPostReport {
    noteHtml: string;
    reasons: IReason[];
    recordType: string;
    recordID: number;
}

/**
 * GET /api/v2/report-reasons
 * GET /api/v2/report-reasons/:reasonID
 */
export interface IReason {
    reportReasonJunctionID: number;
    reportReasonID: string;
    reportID: number;
    name: string;
    description: string;
    sort: number;
    // Check post.moderate permission on the category.
    deleted: boolean;
    roleIDs?: number[];
    roles?: Array<{ name: string; roleID: number }>;
    countReports: number;
}
export interface IReasonPostPatch {
    reason: Partial<IReason>;
    reportReasonID?: IReason["reportReasonID"];
}

// Sorting Reasons PUT /api/v2/report-reasons/sort

/**
 * POST /api/v2/reports/dismiss
 */
export type IDismissReport = {
    verifyRecordUser?: boolean; // If true, the recordUser will become verified and all existing reports of their content will be dismissed.
} & (
    | {
          reportID: number;
      }
    | {
          recordType: string;
          recordID: number;
      }
);

/**
 * POST /api/v2/report-reasons
 * PATCH /api/v2/report-reasons/:reportReasonID
 */
interface IPostReason {
    name: string;
    description: string;
    roleIDs?: number[];
}

/**
 * GET /api/v2/reports/triage
 * {
 *  internalStatus?: string | string[]; // Internal status "resolved" | "unresolved"
 *  placeRecordType?: string;
 *  placeRecordID?: number[] | number;
 *  sort?: "dateInserted" | "-dateInserted";
 *  limit?: number;
 *  page?: number;
 * }
 */
export interface ITriageRecord extends ICommunityManagementRecord {
    // Aggregated
    reportReasons: IReason[];
    countReports: number;
    dateLastReport: string;
    countReportUsers: number;
    reportUserIDs: number[];
    // not all userIDs will be here.
    reportUsers: IUserFragment[];
    recordStatus?: IRecordStatus;
    recordInternalStatus?: IRecordStatus;
    attachments?: IAttachment[];
}

interface IRemovePost {
    removePost?: boolean;
    banUser?: boolean;
    removeMethod?: "delete" | "wipe"; // Wipe leaves Tombstones.
}

interface IRestorePost {
    restorePost?: boolean;
    verifyRecordUser?: boolean;
}

/**
 * POST /api/v2/escalations
 */
export type IPostEscalation = {
    // All other reports for the record will be pulled in automatically.
    recordType?: string;
    recordID?: string;
    name: string;
    status: IEscalation["status"];
    assignedUserID?: number;
    noteBody?: string;
    noteFormat?: string;
} & (
    | {
          reportID?: number;
      }
    | IPostReport
) &
    IRemovePost;

/**
 * GET /api/v2/escalations
 * {
 *  status?: string | string[];
 *  reason?: string[] | string;
 *  placeRecordType?: string;
 *  placeRecordID?: number[] | number;
 *  sort?: "dateInserted" | "recordDateInserted" | "-dateInserted" | "-recordDateInserted" | "dateUpdated" | "-dateUpdated";
 *  limit?: number;
 *  page?: number;
 *  expand?: "reports" | "attachments"
 * }
 */
export interface IEscalation extends ICommunityManagementRecord {
    escalationID: number;
    dateInserted: string;
    dateUpdated: string; // Updated by comments or new report being added.
    insertUserID: number;
    insertUser: IUserFragment;
    updateUserID: number;
    updateUser: IUserFragment;
    status: string;
    name: string;
    countComments: number;
    assignedUserID: number | null;
    assignedUser: IUserFragment | null; // Always expanded
    dateAssigned: string | null;

    // Reports
    reportIDs: number[];
    dateLastReport: string | null;
    reportReasonIDs: string[];
    reportReasons: IReason[];
    countReports: number;
    reportUserIDs: number[];
    countReportUsers: number;
    reportUsers: IUserFragment[];

    // Expands
    attachments?: IAttachment[];
    reports?: IReport[];
}

type IPatchEscalation = {
    status?: IEscalation["status"];
    assignedUserID?: number;
    name?: string;
} & (IRemovePost | IRestorePost);

// Outside of these types
// Update /api/v2/comments
// Add escalation & report expands on /api/v2/discussions and /api/v2/comments
// Update /api/v2/attachments to support escalations.

export enum EscalationStatus {
    OPEN = "open",
    IN_PROGRESS = "in-progress",
    ON_HOLD = "on-hold",
    EXTERNAL_ZENDESK = "external-zendesk",
    DONE = "done",
}

export interface PutReportReasonParams {
    [reportReasonID: IReason["reportReasonID"]]: NonNullable<IReason["sort"]>;
}
