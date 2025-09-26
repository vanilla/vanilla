/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostSettingsFieldMapper } from "@library/features/discussions/forms/PostSettingsFieldMapper";
import { PostSettingsFixture } from "@library/features/discussions/forms/__fixtures__/PostSettings.fixture";
import { render, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { LiveAnnouncer } from "react-aria-live";

describe("PostSettingsFieldMapper", () => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
                staleTime: Infinity,
            },
        },
    });

    const renderWithProvider = (ui: React.ReactElement) => {
        return render(
            <LiveAnnouncer>
                <QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>
            </LiveAnnouncer>,
        );
    };

    beforeEach(() => {
        // Clear query client cache before each test
        queryClient.clear();
    });

    const postTypes = PostSettingsFixture.getMockPostTypes(2);
    const baseProps = {
        discussion: PostSettingsFixture.getMockDiscussionWithPostMeta(),
        currentPostType: postTypes[0],
        targetPostType: postTypes[1],
        postFieldMap: PostSettingsFixture.getMockPostFieldMap(),
        setPostFieldMap: vi.fn(),
    };

    it("does not show warning when all required fields are mapped", () => {
        const props = {
            ...baseProps,
        };

        // Create fields where all required fields are mapped
        const currentFields = PostSettingsFixture.getMockPostFields(2);
        const targetFields = PostSettingsFixture.getMockPostFields(2).map((field) => ({
            ...field,
            postFieldID: `target-${field.postFieldID}`,
        }));

        props.currentPostType.postFields = currentFields;
        props.targetPostType.postFields = targetFields;

        // Create mapping that covers all required fields
        const postFieldMap = {
            [currentFields[0].postFieldID]: {
                currentField: currentFields[0].postFieldID,
                targetField: targetFields[0].postFieldID,
                currentFieldValue: "Current Value",
                targetFieldValue: "Target Value",
            },
            [currentFields[1].postFieldID]: {
                currentField: currentFields[1].postFieldID,
                targetField: targetFields[1].postFieldID,
                currentFieldValue: "Another Current Value",
                targetFieldValue: "Another Target Value",
            },
        };

        props.postFieldMap = postFieldMap;

        renderWithProvider(<PostSettingsFieldMapper {...props} />);

        // Warning message should not be displayed
        expect(screen.queryByText(/There are required fields for the new post type/i)).not.toBeInTheDocument();
    });

    it("handles the case when current post type has no fields", () => {
        const props = {
            ...baseProps,
        };

        // Set current post fields to empty array
        props.currentPostType.postFields = [];

        renderWithProvider(<PostSettingsFieldMapper {...props} />);

        // Component should render without errors
        expect(document.body).toBeInTheDocument();
    });
});
