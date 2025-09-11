/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Vanilla
 * @license gpl-2.0-only
 */

import PostItemFragment from "@library/widget-fragments/PostItemFragment.template";
import "@library/widget-fragments/PostItemFragment.template.css";
import { Meta, StoryObj } from "@storybook/react";
import { PostItemPreviewData } from "./PostItemFragment.previewData";
import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import type React from "react";
import PostItemFragmentPreview from "@library/widget-fragments/PostItemFragment.preview";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";

const meta: Meta<typeof PostItemFragment> = {
    title: "Fragments/PostItem",
    component: PostItemFragment,
};

export default meta;

type Story = StoryObj<typeof PostItemFragment>;

const TemplateFn = (previewData: IFragmentPreviewData<React.ComponentProps<typeof PostItemFragment>>): Story => {
    return {
        render: () => {
            return (
                <PermissionsFixtures.AllPermissions>
                    <PostItemFragmentPreview previewData={previewData.data}>
                        <PostItemFragment {...previewData.data} />
                    </PostItemFragmentPreview>
                </PermissionsFixtures.AllPermissions>
            );
        },
        name: previewData.name,
    };
};

const Discussion = TemplateFn(PostItemPreviewData.Discussion);
const ReadDiscussion = TemplateFn(PostItemPreviewData.ReadDiscussion);
const CheckedDiscussion = TemplateFn(PostItemPreviewData.CheckedDiscussion);
const Question = TemplateFn(PostItemPreviewData.Question);
const Idea = TemplateFn(PostItemPreviewData.Idea);
const Minimal = TemplateFn(PostItemPreviewData.Minimal);
const StressTest = TemplateFn(PostItemPreviewData.StressTest);

export { Discussion, ReadDiscussion, CheckedDiscussion, Question, Idea, Minimal, StressTest };
