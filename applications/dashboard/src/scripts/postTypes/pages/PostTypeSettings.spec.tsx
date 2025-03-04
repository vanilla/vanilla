import { render, screen, waitFor } from "@testing-library/react";
import { PostTypeSettings } from "@dashboard/postTypes/pages/PostTypeSettings";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ComponentProps } from "react";
import MockAdapter from "axios-mock-adapter";
import { mockAPI } from "@library/__tests__/utility";

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
        categoryIDs: [],
    };
}
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

function renderWrappedComponent(props?: Partial<ComponentProps<typeof PostTypeSettings>>) {
    render(
        <QueryClientProvider client={queryClient}>
            <PostTypeSettings {...props} />
        </QueryClientProvider>,
    );
}

describe("PostTypeSettings", () => {
    let api: MockAdapter;
    beforeEach(() => {
        api = mockAPI();
        api.onGet(/post-types/).reply(200, [createMockPostType()]);
    });
    it("Renders", async () => {
        renderWrappedComponent();
        expect(await screen.findByText("Mock Post Type")).toBeInTheDocument();
    });
});
