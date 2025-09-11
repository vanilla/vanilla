/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { mockTagItems } from "@dashboard/tagging/TaggingSettings.fixtures";
import { TagScopeService } from "@dashboard/tagging/TagScopeService";
import TagsTable from "@dashboard/tagging/components/TagsTable";
import { Meta, StoryFn } from "@storybook/react";

export default {
    title: "Dashboard/Tagging/TagsTable",
    decorators: [dashboardCssDecorator],
    component: TagsTable,
    argTypes: {
        onSortChange: { action: "sort changed" },
    },
} as Meta<typeof TagsTable>;

TagScopeService.addScope("siteSectionIDs", {
    id: "subcommunity",
    singular: "subcommunity",
    plural: "subcommunities",
    description: "Select the subcommunities to associate this tag with.",
    placeholder: "Select one or more subcommunities",
    getIDs: (tag) => tag.scope?.siteSectionIDs ?? [],
    filterLookupApi: {
        searchUrl: `/subcommunities?name=%s`,
        singleUrl: `/subcommunities/$siteSectionID:%s`,
        labelKey: "name",
        valueKey: "siteSectionID",
    },
    ModalContentComponent: () => <div>Subcommunity Modal Content</div>,
});

const Template: StoryFn<typeof TagsTable> = (args) => <TagsTable {...args} scopeEnabled={true} />;

export const Default = Template.bind({});
Default.args = {
    tags: mockTagItems,
    isLoading: false,
    sort: undefined,
};

export const Loading = Template.bind({});
Loading.args = {
    tags: [],
    isLoading: true,
    sort: undefined,
};

export const EmptyState = Template.bind({});
EmptyState.args = {
    tags: [],
    isLoading: false,
    sort: undefined,
};
