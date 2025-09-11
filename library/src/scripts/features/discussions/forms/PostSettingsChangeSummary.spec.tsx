/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, screen } from "@testing-library/react";

import { CategoryFixture } from "@vanilla/addon-vanilla/categories/__fixtures__/CategoriesFixture";
import { PostSettingChangeSummary } from "@library/features/discussions/forms/PostSettingsChangeSummary";
import { PostSettingsFixture } from "@library/features/discussions/forms/__fixtures__/PostSettings.fixture";

describe("PostSettingChangeSummary", () => {
    const postTypes = PostSettingsFixture.getMockPostTypes(2);
    const baseProps = {
        discussion: PostSettingsFixture.getMockDiscussionWithPostMeta(),
        targetCategory: CategoryFixture.getCategories(1)[0],
        redirect: true,
        currentPostType: postTypes[0],
        targetPostType: postTypes[1],
        postFieldMap: PostSettingsFixture.getMockPostFieldMap(),
    };

    it("renders category move information when targetCategory is different", () => {
        const props = {
            ...baseProps,
        };

        // Ensure category IDs are different to trigger isMove = true
        props.discussion.categoryID = 123;
        props.targetCategory.categoryID = 456;

        render(<PostSettingChangeSummary {...props} />);

        // The discussion name should be rendered
        expect(screen.getByText(props.discussion.name)).toBeInTheDocument();

        // Category names should be rendered
        const categoryName = props?.discussion?.category?.name ?? "";
        expect(screen.getAllByText(categoryName)[0]).toBeInTheDocument();
        expect(screen.getAllByText(props.targetCategory.name)[0]).toBeInTheDocument();

        // Redirect message should be rendered
        expect(screen.getByText(/A redirect link will be left in the original category/)).toBeInTheDocument();
    });

    it("does not render category move information when categoryID is the same", () => {
        const props = {
            ...baseProps,
        };

        // Set the same category ID to make isMove = false
        props.discussion.categoryID = 123;
        props.targetCategory.categoryID = 123;

        render(<PostSettingChangeSummary {...props} />);

        // Discussion name should not be rendered since there's no move
        expect(screen.queryByText(props.discussion.name)).not.toBeInTheDocument();

        // Redirect message should not be rendered
        expect(screen.queryByText(/A redirect link will be left in the original category/)).not.toBeInTheDocument();
    });

    it("renders field mappings for each post field when post types have fields", () => {
        const props = {
            ...baseProps,
        };

        // Make post types different to trigger isChangeType = true
        props.currentPostType.postTypeID = "original-type";
        props.targetPostType.postTypeID = "target-type";
        props.discussion.postTypeID = "original-type";

        // Ensure there are post fields in current post type
        const mockFields = PostSettingsFixture.getMockPostFields(3);
        props.currentPostType.postFields = mockFields;

        render(<PostSettingChangeSummary {...props} />);

        // There should be one mapping row for each post field plus the header
        const mappingElements = screen.getAllByTestId(/mapping-/);
        expect(mappingElements.length).toBe(mockFields.length);
    });

    it("renders no changes warning when post type and category are the same", () => {
        const props = {
            ...baseProps,
        };

        // Set the same post type and category to trigger the no changes warning
        props.currentPostType.postTypeID = "same-type";
        props.targetPostType.postTypeID = "same-type";
        props.discussion.categoryID = 123;
        props.targetCategory.categoryID = 123;

        render(<PostSettingChangeSummary {...props} />);

        // Should render the warning icon and messages
        expect(screen.getByText("Heads up!")).toBeInTheDocument();
        expect(screen.getByText("It looks like you haven't made any changes yet.")).toBeInTheDocument();
        expect(
            screen.getByText("To update this post, go back and select a different post type or category."),
        ).toBeInTheDocument();
    });
});
