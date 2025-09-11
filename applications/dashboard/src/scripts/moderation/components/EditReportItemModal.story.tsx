/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EditReportItemModal } from "@dashboard/moderation/components/EditReportItemModal";
import { mockCommentReport, mockDiscussionReport } from "../__fixtures__/EditReportItem.Fixture";

export default {
    title: "Dashboard/Community Management/Edit Report Item Modal",
};

export function EditDiscussion() {
    return <EditReportItemModal isVisible onSubmit={async () => {}} onClose={() => {}} report={mockDiscussionReport} />;
}

export function EditComment() {
    return <EditReportItemModal isVisible onSubmit={async () => {}} onClose={() => {}} report={mockCommentReport} />;
}
