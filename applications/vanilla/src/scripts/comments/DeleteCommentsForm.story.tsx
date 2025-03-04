/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useMockedApi } from "@library/__tests__/utility";
import DeleteCommentsForm from "@vanilla/addon-vanilla/comments/DeleteCommentsForm";

export default {
    title: "Comments",
};

export function DeleteSingleCommentStory() {
    useMockedApi((mockApi) => {
        mockApi.onDelete("/comments/list").reply(204, {});
    });

    return <DeleteCommentsForm commentIDs={[5, 6, 7]} close={() => {}} />;
}
