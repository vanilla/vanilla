import { render, screen } from "@testing-library/react";
import { PostTypeSettingsImpl, PostTypeSettings } from "@dashboard/postTypes/pages/PostTypeSettings";
import { PostTypesSettingsContext, IPostTypesSettingsContext } from "@dashboard/postTypes/PostTypeSettingsContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ComponentProps } from "react";

function createMockPostType() {
    return {
        postTypeID: "mock-post-type",
        name: "Mock Post Type",
        parentPostTypeID: "discussion",
        isOriginal: false,
        isActive: false,
        isDeleted: false,
        postButtonLabel: "Mock Post Type Button Label",
        postHelperText: "Mock Post Type Helper Text",
        roleIDs: [],
        countCategories: 30,
        dateInserted: "2024-11-01 16:47:08",
        dateUpdated: "2024-11-01 18:16:48",
        insertUserID: 2,
        updateUserID: 2,
    };
}
const queryClient = new QueryClient();
function renderWrappedComponent(
    values?: Partial<IPostTypesSettingsContext>,
    props?: Partial<ComponentProps<typeof PostTypeSettings>>,
) {
    render(
        <QueryClientProvider client={queryClient}>
            <PostTypesSettingsContext.Provider
                value={{
                    status: {
                        postTypes: "success",
                    },
                    postTypes: [createMockPostType()],
                    postTypesByPostTypeID: {},
                    toggleActive: () => {},
                    ...values,
                }}
            >
                <PostTypeSettingsImpl {...props} />
            </PostTypesSettingsContext.Provider>
        </QueryClientProvider>,
    );
}

describe("PostTypeSettings", () => {
    it("Renders", () => {
        renderWrappedComponent();
        expect(screen.getByText("Mock Post Type")).toBeInTheDocument();
    });
});
