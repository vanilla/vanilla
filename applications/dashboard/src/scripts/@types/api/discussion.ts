/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { IImage } from "@library/@types/api/core";
import { IUserFragment, IUserFragmentAndRoles } from "@library/@types/api/users";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import { ITag } from "@library/features/tags/TagsReducer";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { RecordID } from "@vanilla/utils";
import { IReason } from "@dashboard/moderation/CommunityManagementTypes";
import { PostType, type PostField } from "@dashboard/postTypes/postType.types";
import type { IPostWarning } from "@vanilla/addon-vanilla/contentItem/ContentItemWarning";
import type { VanillaSanitizedHtml } from "@vanilla/dom-utils";

export interface IDiscussion {
    discussionID: RecordID;
    type: string;
    name: string;
    url: string;
    canonicalUrl: string;
    dateInserted: string;
    insertUserID: number;
    lastUserID?: number;
    dateUpdated?: string | null;
    updateUserID?: number | null;
    dateLastComment?: string;
    image?: IImage;

    // Stats
    pinned: boolean;
    closed: boolean;
    score: number;
    sink?: boolean;
    resolved?: boolean;
    countViews: number;
    countComments: number;
    countDiscussions?: number;
    countReactions?: number;
    attributes?: any;

    // expands
    lastUser?: IUserFragment | IUserFragmentAndRoles; // expand;
    insertUser?: IUserFragment | IUserFragmentAndRoles; // expand;
    updateUser?: IUserFragment | IUserFragmentAndRoles; // expand;
    breadcrumbs?: ICrumb[];
    categoryID: number;
    category?: ICategoryFragment;
    excerpt?: string;
    body?: VanillaSanitizedHtml;
    tags?: ITag[];
    warning?: IPostWarning;

    pinLocation?: "recent" | "category";

    // Per-session
    unread?: boolean;
    countUnread?: number;
    bookmarked?: boolean;
    dismissed?: boolean;
    muted?: boolean;

    reactions?: IReaction[];
    statusID: number;
    status?: IRecordStatus;
    internalStatusID?: number;
    internalStatus?: IRecordStatus;
    attachments?: IAttachment[];
    reportMeta?: IReportMeta;
    suggestions?: ISuggestedAnswer[];
    showSuggestions?: boolean;
    permissions?: Record<string, boolean>;
    postTypeID?: PostType["postTypeID"];
    postFields?: PostField[];
    postMeta?: Record<PostField["postFieldID"], any>;
}

export interface IReportMeta {
    countReports: number;
    countReportUsers: number;
    dateLastReport: string;
    reportReasonIDs: string[];
    reportReasons: IReason[];
    reportUserIDs: number[];
    reportUsers: IUserFragment[];
}

export interface IRecordStatus {
    statusID: number;
    name: string;
    state: "open" | "closed";
    recordType: string;
    recordSubType: string;
    log?: IRecordStatusLog;
}

export interface IRecordStatusLog {
    reasonUpdated: string;
    dateUpdated: string;
    updateUser?: IUserFragment;
}

export interface IDiscussionEdit {
    commentID: number;
    discussionID: IDiscussion["discussionID"];
    body: string;
    format: string;
}

export interface IDiscussionEmbed {
    discussionID: IDiscussion["discussionID"];
    type: "quote";
    name: string;
    dateInserted: string;
    dateUpdated: string | null;
    insertUser: IUserFragment;
    url: string;
    format: string;
    body?: string;
    bodyRaw: string;
}

export interface IGetDiscussionListParams {
    limit?: number | string;
    page?: number;
    discussionID?: IDiscussion["discussionID"] | Array<IDiscussion["discussionID"]>;
    expand?: string | string[];
    followed?: boolean;
    suggested?: boolean;
    featuredImage?: boolean;
    fallbackImage?: string;
    sort?: DiscussionListSortOptions;
    pinOrder?: "mixed" | "first";
    type?: string[];
    tagID?: string;
    internalStatusID?: number[];
    statusID?: number[];
    layoutViewType?: LayoutViewType;
    categoryID?: number;
    categoryUrlCode?: string;
    dateInserted?: string;
    dateLastComment?: string;
    hasComments?: boolean;
    insertUserRoleID?: number[];
    sentiment?: string[];
}

export enum DiscussionListSortOptions {
    RECENTLY_COMMENTED = "-dateLastComment",
    RECENTLY_CREATED = "-dateInserted",
    TOP = "-score",
    TRENDING = "-hot",
    OLDEST = "dateInserted",
}
