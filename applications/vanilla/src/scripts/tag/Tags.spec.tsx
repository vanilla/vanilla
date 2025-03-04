/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DiscussionListItemMeta } from "@library/features/discussions/DiscussionListItemMeta";
import { STORY_TAGS } from "@library/storybook/storyData";
import { render, screen } from "@testing-library/react";
import TagWidget from "@vanilla/addon-vanilla/tag/TagWidget";
import { setMeta } from "@library/utility/appUtils";
import PostTagsAsset from "@vanilla/addon-vanilla/posts/PostTagsAsset";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

describe("Tags", () => {
    const discussion = { ...DiscussionFixture.fakeDiscussions[2], url: "/mockPath", name: "Mock Discussion" };
    const tagID = STORY_TAGS[0].tagID;
    describe("Custom Layouts ARE NOT enabled for discussion lists, no tag has a url pointing to new discussion list page.", () => {
        beforeEach(() => {
            setMeta("featureFlags.customLayout.discussionList.Enabled", false);
        });
        it("Tags url in Discussion List Item Meta", () => {
            render(<DiscussionListItemMeta {...discussion} />);
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(false);
        });
        it("Tags url in Tags Widget", () => {
            render(<TagWidget tags={STORY_TAGS} />);
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(false);
        });
        it("Tags url in Tags Asset", () => {
            const queryClient = new QueryClient();
            render(
                <QueryClientProvider client={queryClient}>
                    <DiscussionFixture.PostPageProvider discussion={{ tags: STORY_TAGS }}>
                        <PostTagsAsset />
                    </DiscussionFixture.PostPageProvider>
                </QueryClientProvider>,
            );
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(false);
        });
    });
    describe("Custom Layouts ARE enabled for discussion lists, tags pointing to new discussion list page.", () => {
        beforeEach(() => {
            setMeta("featureFlags.customLayout.discussionList.Enabled", true);
        });
        it("Tags url in Discussion List Item Meta", () => {
            render(<DiscussionListItemMeta {...discussion} />);
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(true);
        });
        it("Tags url in Tags Widget", () => {
            render(<TagWidget tags={STORY_TAGS} />);
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(true);
        });
        it("Tags url in Tags Asset", () => {
            const queryClient = new QueryClient();

            render(
                <QueryClientProvider client={queryClient}>
                    <DiscussionFixture.PostPageProvider discussion={{ tags: STORY_TAGS }}>
                        <PostTagsAsset />
                    </DiscussionFixture.PostPageProvider>
                </QueryClientProvider>,
            );
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(true);
        });
    });
});
