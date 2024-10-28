/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    CommentDraftParentIDAndPath,
    IThreadItem,
    IThreadItemComment,
    IThreadItemHole,
    IThreadItemReply,
} from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { RecordID } from "@vanilla/utils";
import { getLocalStorageOrDefault } from "@vanilla/react-utils";
import { DRAFT_PARENT_ID_AND_PATH_KEY } from "@vanilla/addon-vanilla/thread/components/ThreadCommentEditor";
import { IDiscussion } from "@dashboard/@types/api/discussion";

export const isThreadComment = (threadItem: IThreadItem): threadItem is IThreadItemComment => {
    return threadItem.type === "comment";
};

export const isThreadHole = (threadItem: IThreadItem): threadItem is IThreadItemHole => {
    return threadItem.type === "hole";
};

export const isThreadReply = (threadItem: IThreadItem): threadItem is IThreadItemReply => {
    return threadItem.type === "reply";
};

export const deduplicateThreadItems = (threadItems: IThreadItem[]) => {
    return threadItems.filter((threadItem, index, self) => {
        if (isThreadComment(threadItem)) {
            return (
                self.findIndex((item) => {
                    if (isThreadComment(threadItem) && isThreadComment(item)) {
                        return item.commentID === threadItem.commentID;
                    }
                }) === index
            );
        } else if (isThreadHole(threadItem) || isThreadReply(threadItem)) {
            return true;
        }
    });
};

export const getThreadItemID = (threadItem: IThreadItem) => {
    return threadItem.type === "comment"
        ? threadItem.commentID
        : threadItem.type === "hole"
        ? threadItem.holeID
        : threadItem.replyID;
};

export const getThreadItemByMatchingPathOrID = (threadItems: IThreadItem[], path?: string, itemID?: RecordID) => {
    if (!threadItems) {
        return null;
    }
    for (let item of threadItems) {
        if (isThreadComment(item) && (item.path == path || item.commentID == itemID)) {
            return item;
        }

        if (item["children"] && item["children"].length > 0) {
            const foundItem = getThreadItemByMatchingPathOrID(item["children"], path, itemID);
            if (foundItem) {
                return foundItem;
            }
        }
    }

    return null;
};

export const getDraftParentIDAndPath = (discussionID: IDiscussion["discussionID"]): CommentDraftParentIDAndPath => {
    return getLocalStorageOrDefault(`${DRAFT_PARENT_ID_AND_PATH_KEY}-${discussionID}`, null, true);
};
