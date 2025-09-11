/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";

export const mockDiscussionContent = "Test discussion content";

const discussionReportPartial: Partial<IReport> = {
    recordType: "discussion",
    recordName: "Test Discussion",
    recordHtml: blessStringAsSanitizedHtml(`<p>${mockDiscussionContent}</p>`),
    noteHtml: blessStringAsSanitizedHtml("<p>Test note</p>"),
    recordFormat: "Rich",
    recordUrl: "/discussion/123",
    recordExcerpt: mockDiscussionContent,
};

export const mockDiscussionReport = CommunityManagementFixture.getReport(discussionReportPartial);

export const mockCommentContent = "Test comment content";

const commentReportPartial: Partial<IReport> = {
    recordType: "comment",
    recordName: "", // Comments don't have names
    recordHtml: blessStringAsSanitizedHtml(`<p>${mockCommentContent}</p>`),
    noteHtml: blessStringAsSanitizedHtml("<p>Test note</p>"),
    recordFormat: "Rich",
    recordUrl: "/comment/123",
};

export const mockCommentReport = CommunityManagementFixture.getReport(commentReportPartial);
