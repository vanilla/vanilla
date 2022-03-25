/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";

// Valid conversation participation statuses.
export enum ConversationParticipantStatus {
    PARTICIPATING = "participating",
    DELETED = "deleted",
}

// Available expand parameter values when requesting the conversations index.
export enum GetConversationsExpand {
    ALL = "all",
    INSERT_USER = "insertUser",
    LAST_INSERT_USER = "lastInsertUser",
}

// Fields for conversation participant rows.
interface IConversationParticipant {
    userID: number;
    user: IUserFragment;
    status: ConversationParticipantStatus;
}

// Message details as a child of a conversation row.
interface IConversationMessage {
    insertUserID: number;
    dateInserted: string;
    insertUser: IUserFragment;
}

// Availble parameters for requesting the conversations index.
export interface IGetConversationsRequest {
    insertUserID?: number;
    participantUserID?: number;
    page?: number;
    limit?: number;
    expand?: string;
}

// All conversation row fields.
export interface IConversation {
    conversationID: number;
    name: string;
    body: string;
    url: string;
    dateInserted: string;
    insertUserID: number;
    insertUser?: IUserFragment;
    countParticipants: number;
    participants: IConversationParticipant[];
    countMessages: number;
    unread: boolean;
    countUnread: number;
    lastMessage?: IConversationMessage;
}
