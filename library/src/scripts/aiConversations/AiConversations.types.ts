/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

export interface IReference {
    recordID: string;
    recordType: string;
    name: string;
    url: string;
    dateUpdated?: string;
}
export interface IMessage {
    messageID?: string;
    body?: string;
    feedback?: string | null;
    confidence?: string | null;
    user?: string;
    reaction?: "like" | "dislike" | null;
    references?: IReference[];
}

export interface IAiConversation {
    conversationID: number;
    foreignID: string;
    source: string;
    insertUserID: number;
    dateInserted: string;

    lastMessageID?: string;
    lastMessageBody?: string;
    dateLastMessage?: string;
    messages?: IMessage[];
    references?: IReference[];
}

export interface IPostNewConversationParams {
    body: string;
}

export interface IPostConversationReplyParams {
    body: string;
    conversationID: number;
}

export interface IGetConversationsParams {
    limit?: number;
    offset?: number;
    fields?: string[];
}

export interface IGetConversationParams {
    conversationID: number;
    fields?: string[];
}

export interface IPutMessageReactionParams {
    conversationID: number;
    messageID: string;
    reaction: "like" | "dislike" | null;
}

export interface IPutMessageReactionResponse {
    success: boolean;
}

export interface IAskCommunityParams {
    conversationID: number;
}
export interface IAskCommunityResponse {
    name: string;
    body: string;
    format: string;
    summary: string;
    categoryID: number;
    postType: string;
}
export interface IAiConversationsApi {
    getConversations: (params: IGetConversationsParams) => Promise<IAiConversation[]>;
    getConversation: (params: IGetConversationParams) => Promise<IAiConversation>;
    postNewConversation: (params: IPostNewConversationParams) => Promise<IAiConversation>;
    postConversationReply: (params: IPostConversationReplyParams) => Promise<IAiConversation>;
    putMessageReaction: (params: IPutMessageReactionParams) => Promise<IPutMessageReactionResponse>;
    postAskCommunity: (params: IAskCommunityParams) => Promise<IAskCommunityResponse>;
}
