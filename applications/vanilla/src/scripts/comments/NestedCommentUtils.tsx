/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IThreadItem,
    IThreadItemComment,
    IThreadItemHole,
    IThreadItemReply,
} from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { RecordID } from "@vanilla/utils";

export const isNestedComment = (threadItem: IThreadItem): threadItem is IThreadItemComment => {
    return threadItem.type === "comment";
};

export const isNestedHole = (threadItem: IThreadItem): threadItem is IThreadItemHole => {
    return threadItem.type === "hole";
};

export const isNestedReply = (threadItem: IThreadItem): threadItem is IThreadItemReply => {
    return threadItem.type === "reply";
};

export const deduplicateNestedItems = (threadItems: IThreadItem[]) => {
    return threadItems.filter((threadItem, index, self) => {
        if (isNestedComment(threadItem)) {
            return (
                self.findIndex((item) => {
                    if (isNestedComment(threadItem) && isNestedComment(item)) {
                        return item.commentID === threadItem.commentID;
                    }
                }) === index
            );
        } else if (isNestedHole(threadItem) || isNestedReply(threadItem)) {
            return true;
        }
    });
};

export const getNestedItemID = (threadItem: IThreadItem) => {
    return threadItem.type === "comment"
        ? threadItem.commentID
        : threadItem.type === "hole"
        ? threadItem.holeID
        : threadItem.replyID;
};

export const getNestedItemByMatchingPathOrID = (threadItems: IThreadItem[], path?: string, itemID?: RecordID) => {
    if (!threadItems) {
        return null;
    }
    for (let item of threadItems) {
        if (isNestedComment(item) && (item.path == path || item.commentID == itemID)) {
            return item;
        }

        if (item["children"] && item["children"].length > 0) {
            const foundItem = getNestedItemByMatchingPathOrID(item["children"], path, itemID);
            if (foundItem) {
                return foundItem;
            }
        }
    }

    return null;
};
