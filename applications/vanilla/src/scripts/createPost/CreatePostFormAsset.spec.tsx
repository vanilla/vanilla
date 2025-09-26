/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostTypeFixture } from "@dashboard/postTypes/__fixtures__/PostTypeFixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { mockAPI } from "@library/__tests__/utility";
import { LayoutQueryContext } from "@library/features/Layout/LayoutQueryProvider";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { setMeta } from "@library/utility/appUtils";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, RenderResult, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { CategoryFixture } from "@vanilla/addon-vanilla/categories/__fixtures__/CategoriesFixture";
import CreatePostFormAsset from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset";
import { ParentRecordContextProvider } from "@vanilla/addon-vanilla/posts/ParentRecordContext";
import MockAdapter from "axios-mock-adapter";
import React, { ComponentProps, PropsWithChildren } from "react";
import { LiveAnnouncer } from "react-aria-live";
import { act } from "react-dom/test-utils";
import { DraftContextProvider } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { DraftRecordType, DraftStatus, PostDraftMeta } from "@vanilla/addon-vanilla/drafts/types";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { vitest } from "vitest";

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
});
