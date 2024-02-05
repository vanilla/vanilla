/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment, QnAStatus } from "@dashboard/@types/api/comment";
import { useMutation } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";

export const usePatchAnswerStatus = function (comment: IComment) {
    return useMutation({
        mutationFn: async function (status: QnAStatus) {
            const result = await apiv2.patch<IComment>(`/comments/${comment.commentID}/answer`, {
                status,
            });
            return result.data;
        },
    });
};
