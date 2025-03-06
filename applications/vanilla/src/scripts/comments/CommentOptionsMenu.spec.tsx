/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { screen, waitFor, within } from "@testing-library/react";
import { CommentOptionsMenu } from "@vanilla/addon-vanilla/comments/CommentOptionsMenu";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { CommentFixture } from "@vanilla/addon-vanilla/comments/__fixtures__/Comment.Fixture";
import { setMeta } from "@library/utility/appUtils";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";

describe("CommentOptionsMenu", () => {
    describe("Someones else's comment", () => {
        const notMyComment = { ...CommentFixture.mockComment, insertUserID: 12 };
        describe("With no permissions", () => {
            it("No menu is rendered", async () => {
                await CommentFixture.wrapInProvider(
                    <PermissionsFixtures.NoPermissions>
                        <CommentOptionsMenu
                            comment={notMyComment}
                            commentParent={DiscussionFixture.mockCommentParent}
                            onCommentEdit={() => null}
                            isEditLoading={false}
                            isVisible={true}
                        />
                    </PermissionsFixtures.NoPermissions>,
                );
                expect(screen.queryByTitle("Comment Options")).not.toBeInTheDocument();
            });
        });

        describe("With all permissions", () => {
            it("All options are rendered", async () => {
                await CommentFixture.wrapInProvider(
                    <PermissionsFixtures.AllPermissions>
                        <CommentOptionsMenu
                            comment={notMyComment}
                            commentParent={DiscussionFixture.mockCommentParent}
                            onCommentEdit={() => null}
                            isEditLoading={false}
                            isVisible={true}
                        />
                    </PermissionsFixtures.AllPermissions>,
                );
                expect(screen.queryByTitle("Comment Options")).toBeInTheDocument();
                await waitFor(() => {
                    expect(screen.queryByText(/Edit/)).toBeInTheDocument();
                    expect(screen.queryByText(/Delete/)).toBeInTheDocument();
                    expect(screen.queryByText(/Revision History/)).toBeInTheDocument();
                });
            });
        });
    });

    describe("My own comment, created twenty minutes ago", () => {
        const commentCreatedTwentyMinutesAgo = {
            ...CommentFixture.mockComment,
            dateInserted: new Date(Date.now() - 20 * 60 * 1000).toISOString(),
        };

        describe("Ten minutes remain to edit content", () => {
            beforeEach(async () => {
                // set the edit content timeout to 30 minutes
                setMeta("ui.editContentTimeout", 30 * 60);

                // allow deleting one's own comment
                setMeta("ui.allowSelfDelete", true);

                await CommentFixture.wrapInProvider(
                    <CommentOptionsMenu
                        comment={commentCreatedTwentyMinutesAgo}
                        commentParent={DiscussionFixture.mockCommentParent}
                        onCommentEdit={() => null}
                        isEditLoading={false}
                        isVisible={true}
                    />,
                );
            });
            it("Edit button is rendered. Remaining time is indicated.", async () => {
                const edit = await screen.findByText(/Edit/);
                expect(edit).toBeInTheDocument();
                const editButton = edit.closest("button");
                const timer = await within(editButton!).findByText(/10 minutes/);
                expect(timer).toBeInTheDocument();
            });

            it("Delete button is rendered", async () => {
                expect(await screen.findByText(/Delete/)).toBeInTheDocument();
            });
        });

        describe("Content editing interval has elapsed", () => {
            beforeEach(async () => {
                // set the edit content timeout to 15 minutes
                setMeta("ui.editContentTimeout", 15 * 60);

                await CommentFixture.wrapInProvider(
                    <CommentOptionsMenu
                        comment={commentCreatedTwentyMinutesAgo}
                        commentParent={DiscussionFixture.mockCommentParent}
                        onCommentEdit={() => null}
                        isEditLoading={false}
                        isVisible={true}
                    />,
                );
            });
            it("Edit button is not rendered", () => {
                expect(screen.queryByText(/Edit/)).not.toBeInTheDocument();
            });
        });
    });
});
