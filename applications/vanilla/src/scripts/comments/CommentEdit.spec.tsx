/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { fireEvent, screen, act } from "@testing-library/react";
import { CommentEdit } from "@vanilla/addon-vanilla/comments/CommentEdit";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { ICommentEdit } from "@dashboard/@types/api/comment";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";
import { CommentFixture } from "@vanilla/addon-vanilla/comments/__fixtures__/Comment.Fixture";
import { vitest } from "vitest";
import { CommentSpecFixture } from "@vanilla/addon-vanilla/comments/__fixtures__/CommentSpecFixture";

const MOCK_COMMENT_EDIT: ICommentEdit = {
    commentID: 1,
    parentRecordType: "discussion",
    parentRecordID: 1,
    body: JSON.stringify([
        {
            type: "p",
            children: [{ text: "This is a mock comment." }],
        },
    ]),
    format: "rich2",
};

describe("CommentEditor", () => {
    let mockAdapter: MockAdapter;

    beforeAll(() => {
        mockAdapter = mockAPI();
    });

    it("Renders the editable comment", async () => {
        await CommentSpecFixture.wrapInProvider(
            <CommentEdit
                comment={LayoutEditorPreviewData.comments(1)[0]}
                commentEdit={MOCK_COMMENT_EDIT}
                onClose={() => null}
            />,
        );

        await screen.findByTestId("vanilla-editor");
        const mockCommentText = await screen.findByText("This is a mock comment.");
        expect(mockCommentText).toBeInTheDocument();
    });

    it("Saves the comment body", async () => {
        mockAdapter.onPatch(/(.+)/).reply(200, CommentFixture.comment({}));
        const mockOnSuccess = vitest.fn();

        await CommentSpecFixture.wrapInProvider(
            <CommentEdit
                comment={LayoutEditorPreviewData.comments(1)[0]}
                commentEdit={MOCK_COMMENT_EDIT}
                onClose={() => null}
                onSuccess={mockOnSuccess}
            />,
            true,
        );

        await screen.findByTestId("vanilla-editor");
        const mockCommentText = await screen.findByText("This is a mock comment.");
        expect(mockCommentText).toBeInTheDocument();

        const saveButton = await screen.findByRole("button", { name: "Save" });
        await act(async () => {
            fireEvent.click(saveButton);
        });

        expect(mockAdapter.history.patch.length).toBe(1);
        expect(mockOnSuccess).toHaveBeenCalled();
    });
});
