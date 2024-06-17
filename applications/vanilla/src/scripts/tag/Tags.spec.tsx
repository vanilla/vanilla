/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { DiscussionListItemMeta } from "@library/features/discussions/DiscussionListItemMeta";
import { STORY_TAGS } from "@library/storybook/storyData";
import { render, screen } from "@testing-library/react";
import TagWidget from "@vanilla/addon-vanilla/tag/TagWidget";
import { setMeta } from "@library/utility/appUtils";
import DiscussionTagAsset from "@vanilla/addon-vanilla/thread/DiscussionTagAsset";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";

describe("Tags", () => {
    const discussion = { ...DiscussionFixture.fakeDiscussions[2], url: "/mockPath", name: "Mock Discussion" };
    const tagID = STORY_TAGS[0].tagID;
    describe("Custom Layouts ARE NOT enabled for discussion lists, no tag has a url pointing to new discussion list page.", () => {
        beforeEach(() => {
            setMeta("featureFlags.customLayout.discussionList.Enabled", false);
        });
        it("Tags url in Discussion List Item Meta", () => {
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
                        },
                    }}
                >
                    <DiscussionListItemMeta {...discussion} />
                </TestReduxProvider>,
            );
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(false);
        });
        it("Tags url in Tags Widget", () => {
            render(<TagWidget tags={STORY_TAGS} />);
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(false);
        });
        it("Tags url in Tags Asset", () => {
            render(<DiscussionTagAsset tags={STORY_TAGS} />);
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(false);
        });
    });
    describe("Custom Layouts ARE enabled for discussion lists, tags pointing to new discussion list page.", () => {
        beforeEach(() => {
            setMeta("featureFlags.customLayout.discussionList.Enabled", true);
        });
        it("Tags url in Discussion List Item Meta", () => {
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
                        },
                    }}
                >
                    <DiscussionListItemMeta {...discussion} />
                </TestReduxProvider>,
            );
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(true);
        });
        it("Tags url in Tags Widget", () => {
            render(<TagWidget tags={STORY_TAGS} />);
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(true);
        });
        it("Tags url in Tags Asset", () => {
            render(<DiscussionTagAsset tags={STORY_TAGS} />);
            const userTag = screen.queryByRole("link", { name: "UserTag" });
            expect(userTag?.getAttribute("href")?.includes(`/discussions?tagID=${tagID}`)).toBe(true);
        });
    });
});
