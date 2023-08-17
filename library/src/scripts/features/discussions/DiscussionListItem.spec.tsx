/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import DiscussionListItem from "@library/features/discussions/DiscussionListItem";
import { fakeDiscussions } from "@library/features/discussions/DiscussionList.story";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { setMeta } from "@library/utility/appUtils";

const renderInProvider = () => {
    const discussion = { ...fakeDiscussions[0], url: "/mockPath", name: "Mock Discussion" };
    render(
        <TestReduxProvider
            state={{
                discussions: {
                    discussionsByID: {
                        10: {
                            status: LoadStatus.SUCCESS,
                            data: discussion,
                        },
                    },
                    bookmarkStatusesByID: {
                        10: LoadStatus.SUCCESS,
                    },
                },
            }}
        >
            <DiscussionListItem discussion={discussion} />
        </TestReduxProvider>,
    );
};

describe("DiscussionListItem", () => {
    it("Default link behavior goes to #latest", () => {
        renderInProvider();
        const discussionLink = screen.queryByRole("link", { name: "Mock Discussion" });
        expect(discussionLink).toHaveAttribute("href", "http://localhost/mockPath#latest");
    });

    it("When auto offset is disabled, url does not go to #latest", () => {
        setMeta("ui.autoOffsetComments", false);
        renderInProvider();
        const discussionLink = screen.queryByRole("link", { name: "Mock Discussion" });
        expect(discussionLink).toHaveAttribute("href", "http://localhost/mockPath");
    });
});
