/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import "./CommentItemFragment.template.css";

import CommentItemFragment from "@library/widget-fragments/CommentItemFragment.template";
import { IReportMeta } from "@dashboard/@types/api/discussion";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { STORY_COMMENTS } from "@library/storybook/storyData";
import { CommentItemFragmentContextProvider } from "@vanilla/addon-vanilla/comments/CommentItemFragmentContext";

export default {
    title: "Fragments/CommentItem",
};

export function Template() {
    const comment = {
        ...STORY_COMMENTS[1],
        reportMeta: {
            countReports: 12,
        } as IReportMeta,
    };
    return (
        <PermissionsFixtures.AllPermissions>
            <CommentItemFragmentContextProvider comment={comment} categoryID={0} onReply={() => {}} isEditing={false}>
                <CommentItemFragment comment={comment} categoryID={0} onReply={() => {}} isEditing={false} />
            </CommentItemFragmentContextProvider>
        </PermissionsFixtures.AllPermissions>
    );
}
