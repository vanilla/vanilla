/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DraftRecordType, DraftStatus, PostDraftMeta } from "@vanilla/addon-vanilla/drafts/types";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import React, { ComponentProps, PropsWithChildren } from "react";
import { RenderResult, render, screen, waitFor, within } from "@testing-library/react";

import { CategoryFixture } from "@vanilla/addon-vanilla/categories/__fixtures__/CategoriesFixture";
import CreatePostFormAsset from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset";
import { DraftContextProvider } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { LayoutQueryContext } from "@library/features/Layout/LayoutQueryProvider";
import { LiveAnnouncer } from "react-aria-live";
import MockAdapter from "axios-mock-adapter";
import { ParentRecordContextProvider } from "@vanilla/addon-vanilla/posts/ParentRecordContext";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { PostTypeFixture } from "@dashboard/postTypes/__fixtures__/PostTypeFixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { act } from "react-dom/test-utils";
import { mockAPI } from "@library/__tests__/utility";
import { setMeta } from "@library/utility/appUtils";
import userEvent from "@testing-library/user-event";
import { vitest } from "vitest";

const mockPushSmartLocation = vi.fn();
vi.mock("@library/routing/links/linkUtils", () => ({
    pushSmartLocation: (...args: any[]) => mockPushSmartLocation(...args),
}));

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: true,
            retry: false,
            staleTime: Infinity,
        },
    },
});

const mockCategories = CategoryFixture.getCategories(5, { allowedPostTypeOptions: PostTypeFixture.getPostTypes(3) });
const mockCategory = mockCategories[0];
const mockPostTypes = PostTypeFixture.getPostTypes(5);

const mockDraftName = "Mock Draft Name";
const mockDraftBodyText = "Hello World";
const mockNewTagNames = ["mockTag1", "mockTag2"];

const mockDiscussionDraft: IDraft = {
    draftID: 999,
    draftStatus: DraftStatus.DRAFT,
    breadCrumbs: [],
    recordType: DraftRecordType.DISCUSSION,
    insertUserID: 1,
    dateInserted: new Date().toISOString(),
    updateUserID: 1,
    dateUpdated: new Date().toISOString(),
    attributes: {
        body: `[{"type":"p","children":[{"text":${mockDraftBodyText}}]}]`,
        format: "rich2",
        draftType: "discussion",
        lastSaved: new Date().toISOString(),
        draftMeta: {
            name: mockDraftName,
            categoryID: mockCategory.categoryID,
            postTypeID: mockPostTypes[0].postTypeID,
            newTagNames: mockNewTagNames,
            pinned: false,
            pinLocation: "none",
        } as Partial<PostDraftMeta>,
    },
    editUrl: "",
};

export function TestWrapper(
    props: PropsWithChildren<{
        layoutQueryOverrides?: Partial<ILayoutQuery["params"]>;
    }>,
) {
    const { layoutQueryOverrides, children } = props;

    const layoutQuery: ILayoutQuery = {
        layoutViewType: "mockLayoutType",
        params: {
            parentRecordType: "category",
            parentRecordID: mockCategory.categoryID,
            ...layoutQueryOverrides,
        },
    };
    return (
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider>
                <LiveAnnouncer>
                    <LayoutQueryContext.Provider value={{ layoutQuery }}>
                        <ParentRecordContextProvider>{children}</ParentRecordContextProvider>
                    </LayoutQueryContext.Provider>
                </LiveAnnouncer>
            </TestReduxProvider>
        </QueryClientProvider>
    );
}

export function WrappedNewPostForm(props?: Partial<ComponentProps<typeof CreatePostFormAsset>>) {
    return <CreatePostFormAsset taggingEnabled={false} category={mockCategory} {...props} />;
}

export const mockGetCategories = vi.fn(async () => {});
export const mockGetCategory = vi.fn(async () => {});
export const mockGetPostTypes = vi.fn(async () => {});
export const mockPostDiscussion = vi.fn(async () => {});

export function setUpMockApi(): MockAdapter {
    const mockApi = mockAPI();
    mockApi.onGet(`/categories/${mockCategory.categoryID}`).reply(async () => {
        await mockGetCategory();
        return [200, mockCategory];
    });
    mockApi.onGet("/categories").reply(async (config) => {
        if (config.params.categoryID) {
            await mockGetCategory();
            return [200, mockCategory];
        } else {
            await mockGetCategories();
            return [200, mockCategories];
        }
    });

    mockApi.onGet(/categories\?filterDiscussionsAdd=(.*)/).reply(async () => {
        await mockGetCategories();
        return [200, mockCategories];
    });

    mockApi.onGet("/post-types").reply(async () => {
        await mockGetPostTypes();
        return [200, mockPostTypes];
    });

    return mockApi;
}

describe("CreatePostFormAsset", () => {
    let mockApi: MockAdapter;
    let result: RenderResult;

    beforeEach(() => {
        mockApi = setUpMockApi();
        mockPushSmartLocation.mockClear();
        Object.defineProperty(window, "location", {
            value: {
                href: "https://mysite.com/test",
            },
            writable: true,
        });
    });

    async function waitForInitialCalls() {
        await vitest.waitFor(async () => expect(mockGetCategory).toHaveReturned());
        await vitest.waitFor(async () => expect(mockGetPostTypes).toHaveReturned());
        await vitest.waitFor(async () => expect(mockGetCategories).toHaveReturned());
    }

    it("Pre-populates Category Field", async () => {
        await act(async () => {
            result = render(
                <TestWrapper>
                    <WrappedNewPostForm taggingEnabled={false} />
                </TestWrapper>,
            );
            await waitForInitialCalls();
            await vitest.waitFor(async () => expect(mockGetCategories).toHaveReturnedTimes(2));
        });

        expect(result.getAllByText(mockCategories[0].name)[0]).toBeInTheDocument();
    });

    it("Filters available post types", async () => {
        await act(async () => {
            result = render(
                <TestWrapper>
                    <WrappedNewPostForm />
                </TestWrapper>,
            );
            await waitForInitialCalls();
        });

        const postTypeDropdown = result.getAllByTestId("inputContainer")[1];
        await userEvent.click(postTypeDropdown);

        expect(await result.findByText(mockPostTypes[0].name)).toBeInTheDocument();
        expect(result.queryByText(mockPostTypes[4].name)).not.toBeInTheDocument();
    });

    it("Cannot announce post without permission", async () => {
        await act(async () => {
            result = render(
                <TestWrapper>
                    <WrappedNewPostForm />
                </TestWrapper>,
            );
            await waitForInitialCalls();
        });

        const announceDropdown = result.queryByText("Don't announce");
        expect(announceDropdown).not.toBeInTheDocument();
    });

    it("Can announce post with appropriate permission", async () => {
        await act(async () => {
            result = render(
                <TestWrapper>
                    <PermissionsFixtures.SpecificPermissions permissions={["discussions.announce"]}>
                        <WrappedNewPostForm />
                    </PermissionsFixtures.SpecificPermissions>
                </TestWrapper>,
            );
            await waitForInitialCalls();
        });

        const announceDropdown = result.queryByText("Don't announce");
        expect(announceDropdown).toBeInTheDocument();
    });

    describe("Drafts", () => {
        it("Draft Schedule button available if we have the permission", async () => {
            setMeta("featureFlags.DraftScheduling.Enabled", true);
            await act(async () => {
                result = render(
                    <TestWrapper>
                        <PermissionsFixtures.SpecificPermissions permissions={["schedule.allow"]}>
                            <WrappedNewPostForm />
                        </PermissionsFixtures.SpecificPermissions>
                    </TestWrapper>,
                );
                await waitForInitialCalls();
            });

            expect(await result.findByText("Schedule")).toBeInTheDocument();
        });

        describe("Loading a draft", () => {
            function DraftTestWrapper(props: React.ComponentProps<typeof TestWrapper>) {
                const { children, ...rest } = props;
                return (
                    <TestWrapper {...rest}>
                        <DraftContextProvider
                            serverDraftID={mockDiscussionDraft.draftID}
                            serverDraft={mockDiscussionDraft}
                            recordType={DraftRecordType.DISCUSSION}
                            autosaveEnabled={false}
                            loadLocalDraftByMatchers={false}
                            parentRecordID={mockCategory.categoryID}
                        >
                            {children}
                        </DraftContextProvider>
                    </TestWrapper>
                );
            }

            afterEach(() => {
                localStorage.clear();
                queryClient.clear();
            });

            beforeEach(() => {
                mockApi.onGet(/tags/).reply(200, []);
                vitest.mock("react-router-dom", () => ({
                    ...vitest.importActual("react-router-dom"),
                    useLocation: () => ({
                        pathname: "mock-pathname",
                    }),
                }));
            });

            describe("With tagging disabled", () => {
                it("Form does not contain tag field", async () => {
                    await act(async () => {
                        result = render(
                            <DraftTestWrapper>
                                <WrappedNewPostForm taggingEnabled={false} />
                            </DraftTestWrapper>,
                        );
                        await waitForInitialCalls();
                    });

                    expect(result.queryByLabelText("Tags")).not.toBeInTheDocument();
                });
            });

            describe("With tagging enabled", () => {
                let form: HTMLFormElement;
                let tagsGroup: HTMLElement;

                describe("With the `tags.add` permission", () => {
                    beforeEach(async () => {
                        await act(async () => {
                            result = render(
                                <DraftTestWrapper>
                                    <PermissionsFixtures.SpecificPermissions permissions={["tags.add"]}>
                                        <WrappedNewPostForm taggingEnabled={true} />
                                    </PermissionsFixtures.SpecificPermissions>
                                </DraftTestWrapper>,
                            );
                            await waitForInitialCalls();
                        });
                        form = (await result.findByRole("form")) as HTMLFormElement;
                        tagsGroup = await within(form).findByRole("group", { name: "Tags", exact: true });
                    });

                    it("Pre-populates tag field with new tags", async () => {
                        const tokens = await within(tagsGroup).findAllByText((_content, element) => {
                            return element ? element.classList.contains("token") : false;
                        });

                        expect(tokens.length).toBe(mockNewTagNames.length);
                        expect(tokens[0]).toHaveTextContent(mockNewTagNames[0]);
                        expect(tokens[1]).toHaveTextContent(mockNewTagNames[1]);
                    });
                });

                describe("Without the `tags.add` permission", () => {
                    beforeEach(async () => {
                        await act(async () => {
                            result = render(
                                <DraftTestWrapper>
                                    <WrappedNewPostForm taggingEnabled={true} />
                                </DraftTestWrapper>,
                            );
                            await waitForInitialCalls();
                        });
                        form = (await result.findByRole("form")) as HTMLFormElement;
                        tagsGroup = await within(form).findByRole("group", { name: "Tags", exact: true });
                    });

                    it("Has a tags input but does not add the new tag names", async () => {
                        mockNewTagNames.forEach((newTagName) => {
                            expect(within(tagsGroup).queryByText(newTagName)).not.toBeInTheDocument();
                        });
                    });
                });
            });
        });
    });

    describe("Silent Posting", () => {
        beforeEach(() => {
            mockPostDiscussion.mockClear();
        });

        async function openPublishNotificationModal() {
            await act(async () => {
                render(
                    <TestWrapper>
                        <PermissionsFixtures.SpecificPermissions permissions={["silentPosting.allow"]}>
                            <WrappedNewPostForm postType={mockPostTypes[0]} />
                        </PermissionsFixtures.SpecificPermissions>
                    </TestWrapper>,
                );
                await waitForInitialCalls();
            });

            await vitest.dynamicImportSettled();

            await act(async () => {
                // Fill in the title field
                const titleInput = await screen.findByText("Mock Post Type 1 Title");
                await userEvent.type(titleInput, "Test Discussion Title");

                // Click the Post button
                const postButton = await screen.findByRole("button", { name: "Post" });
                await userEvent.click(postButton);
                await vitest.dynamicImportSettled();
            });
        }

        it("Opens PublishNotificationModal when user has silentPosting.allow permission", async () => {
            await openPublishNotificationModal();
            // Wait for and verify the modal appears
            await waitFor(() => {
                expect(screen.getByText(/do you want to notify followers about this post/i)).toBeInTheDocument();
            });
        });

        it("Sends publishedSilently: true when 'No, publish this post silently' is selected", async () => {
            let actualRequestBody: any;
            mockApi.onPost(/discussions.*/).reply((config) => {
                actualRequestBody = JSON.parse(config.data);
                return [200, { discussionID: 123, canonicalUrl: "/discussion/123/test" }];
            });

            await openPublishNotificationModal();

            // Wait for the modal to appear and select silent posting
            await waitFor(() => {
                expect(screen.getByText(/do you want to notify followers about this post/i)).toBeInTheDocument();
            });

            await act(async () => {
                const silentOption = await screen.findByText("No, publish this post silently");
                await userEvent.click(silentOption);
            });

            await act(async () => {
                const okButton = await screen.findByRole("button", { name: "OK" });
                await userEvent.click(okButton);
            });

            // Verify that the API was called with publishedSilently: true
            await waitFor(() => {
                expect(actualRequestBody).toMatchObject(
                    expect.objectContaining({
                        publishedSilently: true,
                    }),
                );
            });
        });

        it("Omits publishedSilently field when 'Yes, send notifications' is selected", async () => {
            let actualRequestBody: any;
            mockApi.onPost(/discussions.*/).reply((config) => {
                actualRequestBody = JSON.parse(config.data);
                return [200, {}];
            });

            await openPublishNotificationModal();

            // Wait for the modal to appear
            await waitFor(() => {
                expect(screen.getByText(/do you want to notify followers about this post/i)).toBeInTheDocument();
            });

            await act(async () => {
                const notifyOption = await screen.findByText("Yes, send notifications");
                await userEvent.click(notifyOption);

                const okButton = await screen.findByRole("button", { name: "OK" });
                await userEvent.click(okButton);
                await vitest.dynamicImportSettled();
            });

            await waitFor(() => {
                expect(actualRequestBody).toMatchObject(
                    expect.not.objectContaining({
                        publishedSilently: true,
                    }),
                );
            });
        });
    });
});
