/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import userEvent from "@testing-library/user-event";
import { ConvertPostFormImpl } from "@library/features/discussions/forms/ConvertPostForm.loadable";
import {
    MOCK_POST_TYPES,
    MOCK_SOURCE_ID,
    MOCK_DESTINATION_ID,
} from "@library/features/discussions/forms/ConvertPostForm.fixtures";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

// For the NestedSelect, chooses the nth item in the list (index n + 1)
async function chooseNthItemInList(input: HTMLElement, n = 1) {
    input.focus();

    while (n > 0) {
        await userEvent.keyboard("{ArrowDown}");
        n--;
    }

    return await userEvent.keyboard("{Enter}");
}

function renderInProvider(sourcePostTypeID = MOCK_SOURCE_ID) {
    render(
        <QueryClientProvider client={queryClient}>
            <ConvertPostFormImpl
                allPostTypes={MOCK_POST_TYPES}
                onClose={() => {}}
                sourcePostTypeID={sourcePostTypeID}
                discussionID={1}
            />
        </QueryClientProvider>,
    );
}

describe("Convert post form", () => {
    it("should render the form", () => {
        renderInProvider();

        expect(screen.getByText("Change Post Type")).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "Cancel" })).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "Save" })).toBeInTheDocument();
    });

    it("should render the source post type", () => {
        renderInProvider();
        const sourcePostType = MOCK_POST_TYPES.find((postType) => postType.postTypeID === MOCK_SOURCE_ID);

        expect(screen.getByLabelText("Current post type")).toHaveValue(sourcePostType!.name);
    });

    it("should show the source post type as the default destination post type", () => {
        renderInProvider();

        const sourcePostType = MOCK_POST_TYPES.find((postType) => postType.postTypeID === MOCK_SOURCE_ID);

        const destinationInput = screen.getByRole("textbox", { name: "Select post type to change to" });

        expect(destinationInput).toHaveValue(sourcePostType!.name);
    });

    it("should allow the user to select a destination post type", async () => {
        renderInProvider();

        const destinationInput = screen.getByRole("textbox", { name: "Select post type to change to" });

        expect(destinationInput).toBeInTheDocument();

        await chooseNthItemInList(destinationInput, 2);

        const destinationPostType = MOCK_POST_TYPES.find((postType) => postType.postTypeID === MOCK_DESTINATION_ID);

        expect(destinationInput).toHaveValue(destinationPostType!.name);
    });

    describe("if both source and destination post types have no custom fields", () => {
        it("should display a message instead of the form", async () => {
            renderInProvider("discussion");

            const destinationInput = screen.getByRole("textbox", { name: "Select post type to change to" });

            await chooseNthItemInList(destinationInput, 4);

            expect(destinationInput).toHaveValue("Idea");
            expect(screen.getByText("No custom fields used in either post type")).toBeInTheDocument();
        });
    });

    describe("When either post type contains custom fields", () => {
        it("should show the source fields", () => {
            renderInProvider();

            const sourcePostType = MOCK_POST_TYPES.find((postType) => postType.postTypeID === MOCK_SOURCE_ID);
            const sourceFields = sourcePostType!.postFields;

            sourceFields?.forEach((field) => {
                expect(screen.queryByDisplayValue(field?.label)).toBeInTheDocument();
            });
        });

        it("should pre-map matching fields", async () => {
            renderInProvider();

            const destinationInput = screen.getByRole("textbox", { name: "Select post type to change to" });

            await chooseNthItemInList(destinationInput, 1);

            const sourcePostType = MOCK_POST_TYPES.find((postType) => postType.postTypeID === MOCK_SOURCE_ID);
            const destinationPostType = MOCK_POST_TYPES.find((postType) => postType.postTypeID === MOCK_DESTINATION_ID);

            const sourceFields = sourcePostType!.postFields;
            const destinationFields = destinationPostType!.postFields;

            sourceFields?.forEach((sourceField) => {
                const matchingField = destinationFields?.find(
                    (field) => field.postFieldID === sourceField?.postFieldID,
                );

                if (matchingField) {
                    expect(screen.queryByDisplayValue(sourceField?.label)).toHaveValue(matchingField.label);
                } else {
                    expect(screen.queryByDisplayValue(sourceField?.label)).not.toHaveValue("");
                }
            });
        });

        it("should allow users to select a field to map to", async () => {
            renderInProvider();

            const destinationInput = screen.getByRole("textbox", { name: "Select post type to change to" });
            const destinationPosition = 6;

            await chooseNthItemInList(destinationInput, destinationPosition);

            const sourcePostType = MOCK_POST_TYPES.find((postType) => postType.postTypeID === MOCK_SOURCE_ID);
            const destinationPostType = MOCK_POST_TYPES[destinationPosition - 1];

            const sourceFields = sourcePostType!.postFields;
            const destinationFields = destinationPostType!.postFields;

            const firstField = sourceFields![0];

            const sourceFieldInput = screen.getByRole("textbox", { name: firstField.label });

            await chooseNthItemInList(sourceFieldInput, 1);

            expect(sourceFieldInput).toBeInTheDocument();
            expect(sourceFieldInput).toHaveValue(destinationFields![0].label);
        });

        it("should not allow a private or internal field to be mapped to public", async () => {
            renderInProvider("test-with-private-field");

            const destinationInput = screen.getByRole("textbox", { name: "Select post type to change to" });
            const destinationPosition = 1;
            const sourcePosition = 6;

            await chooseNthItemInList(destinationInput, destinationPosition);

            const sourcePostType = MOCK_POST_TYPES[sourcePosition - 1];

            const sourceFields = sourcePostType!.postFields;

            const firstField = sourceFields![0];

            const sourceFieldInput = screen.getByRole("textbox", { name: firstField.label });

            sourceFieldInput.focus();
            await userEvent.keyboard("{ArrowDown}");

            const menuItems = screen.getAllByRole("menuitem");

            // There should be only one option to map to, which is a default and not a real field
            expect(menuItems).toHaveLength(1);
            expect(menuItems[0]).toHaveTextContent("Discard this information");
        });

        it("should allow a public field to be mapped to a private or internal field", async () => {
            renderInProvider("test-with-private-field");

            const destinationInput = screen.getByRole("textbox", { name: "Select post type to change to" });
            const destinationPosition = 2;
            const sourcePosition = 6;

            await chooseNthItemInList(destinationInput, destinationPosition);

            const sourcePostType = MOCK_POST_TYPES[sourcePosition - 1];

            const sourceFields = sourcePostType!.postFields;

            const secondSourceInput = screen.getByRole("textbox", { name: sourceFields![1].label });

            expect(secondSourceInput).toBeInTheDocument();

            await chooseNthItemInList(secondSourceInput, 2);
            expect(secondSourceInput).toBeInTheDocument();
            expect(secondSourceInput).toHaveValue("Service Provider"); // internal field
        });
    });
});
