/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IThreadItem,
    IThreadItemComment,
    IThreadItemHole,
} from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";

export const isThreadComment = (threadItem: IThreadItem): threadItem is IThreadItemComment => {
    return threadItem.type === "comment";
};

export const isThreadHole = (threadItem: IThreadItem): threadItem is IThreadItemHole => {
    return threadItem.type === "hole";
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
        } else if (isThreadHole(threadItem)) {
            return true;
        }
    });
};
