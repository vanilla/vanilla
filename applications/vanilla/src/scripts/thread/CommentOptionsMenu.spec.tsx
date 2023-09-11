/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { screen } from "@testing-library/react";
import { CommentOptionsMenu } from "@vanilla/addon-vanilla/thread/CommentOptionsMenu";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";

describe("CommentOptionsMenu", () => {
    it("has no options if you have no permissions", () => {
        CommentFixture.wrapInProvider(
            <PermissionsFixtures.NoPermissions>
                <CommentOptionsMenu
                    comment={{ ...CommentFixture.mockComment, insertUserID: 12 }}
                    discussion={DiscussionFixture.mockDiscussion}
                    onCommentEdit={() => null}
                    isEditLoading={false}
                    isVisible={true}
                />
            </PermissionsFixtures.NoPermissions>,
        );
        expect(screen.queryByText(/Edit/)).not.toBeInTheDocument();
        expect(screen.queryByText(/Delete/)).not.toBeInTheDocument();
        expect(screen.queryByText(/Revision History/)).not.toBeInTheDocument();
    });
    it("has some options if its your comment", () => {
        CommentFixture.wrapInProvider(
            <CommentOptionsMenu
                comment={CommentFixture.mockComment}
                discussion={DiscussionFixture.mockDiscussion}
                onCommentEdit={() => null}
                isEditLoading={false}
                isVisible={true}
            />,
        );
        expect(screen.queryByText(/Edit/)).toBeInTheDocument();
        expect(screen.queryByText(/Delete/)).toBeInTheDocument();
        expect(screen.queryByText(/Revision History/)).not.toBeInTheDocument();
    });
    it("has all options if you are an admin", () => {
        CommentFixture.wrapInProvider(
            <PermissionsFixtures.AllPermissions>
                <CommentOptionsMenu
                    comment={CommentFixture.mockComment}
                    discussion={DiscussionFixture.mockDiscussion}
                    onCommentEdit={() => null}
                    isEditLoading={false}
                    isVisible={true}
                />
            </PermissionsFixtures.AllPermissions>,
        );
        expect(screen.queryByText(/Edit/)).toBeInTheDocument();
        expect(screen.queryByText(/Delete/)).toBeInTheDocument();
        expect(screen.queryByText(/Revision History/)).toBeInTheDocument();
    });
});
