/**
 * @author Mihran Abrahamian <mabrahamian@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { render, act, cleanup, fireEvent, within, RenderResult } from "@testing-library/react";
import { SearchFormContextProvider } from "@library/search/SearchFormContextProvider";
import { SearchPageContent } from "./SearchPage";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { SearchService } from "./SearchService";
import DISCUSSIONS_SEARCH_DOMAIN from "@vanilla/addon-vanilla/search/DiscussionsSearchDomain";
import {
    MOCK_ASYNC_SEARCH_DOMAIN,
    MOCK_ASYNC_SEARCH_DOMAIN_LOADABLE,
    MOCK_SEARCH_DOMAIN,
    MockSearchSource,
    MockSearchSourceWithAsyncDomains,
} from "./__fixtures__/Search.fixture";
import MEMBERS_SEARCH_DOMAIN from "@dashboard/components/panels/MembersSearchDomain";
import PLACES_SEARCH_DOMAIN from "@dashboard/components/panels/PlacesSearchDomain";
import { LiveAnnouncer } from "react-aria-live";
import { SiteSectionContext } from "@library/utility/SiteSectionContext";
import { mockSiteSection } from "@library/utility/__fixtures__/SiteSection.fixtures";
import LOADABLE_DISCUSSIONS_SEARCH_DOMAIN from "@vanilla/addon-vanilla/search/DiscussionsSearchDomain.loadable";
import LOADABLE_MEMBERS_SEARCH_DOMAIN from "@dashboard/components/panels/MembersSearchDomain.loadable";
import { MemoryRouter } from "react-router";
import { PlacesSearchTypeFilter } from "@dashboard/components/panels/PlacesSearchTypeFilter";
import { ISearchForm } from "./searchTypes";
import LOADABLE_PLACES_SEARCH_DOMAIN from "@dashboard/components/panels/PlacesSearchDomain.loadable";

const MOCK_SEARCH_SOURCE = new MockSearchSource();

const MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS = new MockSearchSourceWithAsyncDomains();

function MockSearchPage(props: {
    initialFormState?: React.ComponentProps<typeof SearchFormContextProvider>["initialFormState"];
}) {
    return (
        <MemoryRouter>
            <PermissionsFixtures.AllPermissions>
                <LiveAnnouncer>
                    <SearchFormContextProvider initialFormState={props.initialFormState}>
                        <SearchPageContent />
                    </SearchFormContextProvider>
                </LiveAnnouncer>
            </PermissionsFixtures.AllPermissions>
        </MemoryRouter>
    );
}

afterEach(() => {
    cleanup();
    SearchService.sources = [];
    MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.clearDomains();
    MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.performSearch.mockClear();
    MOCK_SEARCH_SOURCE.domains = [];
    MOCK_SEARCH_SOURCE.performSearch.mockClear();
    MOCK_SEARCH_DOMAIN.transformFormToQuery.mockClear();
});

describe("SearchPage", () => {
    let result: RenderResult;

    describe("Single Search Source", () => {
        it("does not render tabs", async () => {
            SearchService.addSource(MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS);

            await act(async () => {
                result = render(<MockSearchPage />);
            });

            const tabs = result.queryAllByRole("tab");
            expect(tabs).toHaveLength(0);
        });
    });
    describe("Multiple Search Sources", () => {
        beforeEach(() => {
            MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.addDomain(DISCUSSIONS_SEARCH_DOMAIN);
            SearchService.addSource(MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS);
            MOCK_SEARCH_SOURCE.addDomain(MOCK_SEARCH_DOMAIN);
            SearchService.addSource(MOCK_SEARCH_SOURCE);
        });

        it("it renders multiple tabs", async () => {
            await act(async () => {
                result = render(<MockSearchPage />);
            });

            const tabs = await result.findAllByRole("tab");
            expect(tabs).toHaveLength(2);
            const tabOneLabel = await within(tabs[0]).findByText(MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.label);
            expect(tabOneLabel).toBeInTheDocument();

            const tabTwoLabel = await within(tabs[1]).findByText(MOCK_SEARCH_SOURCE.label);
            expect(tabTwoLabel).toBeInTheDocument();
        });

        describe("Changing SearchSource", () => {
            it("performs a search on the newly selected SearchSource", async () => {
                await act(async () => {
                    result = render(<MockSearchPage />);
                    const form = await result.findByRole("search");
                    const searchQueryInput = await within(form).findByLabelText("Search Text");
                    await act(async () => {
                        fireEvent.change(searchQueryInput, { target: { value: "test" } });
                    });
                    await act(async () => {
                        fireEvent.submit(form);
                    });
                });

                expect(MOCK_SEARCH_SOURCE.performSearch).not.toHaveBeenCalled();

                const tabTwo = await result.findByRole("tab", { name: MOCK_SEARCH_SOURCE.label });

                await act(async () => {
                    fireEvent.click(tabTwo);
                });

                expect(MOCK_SEARCH_SOURCE.performSearch).toHaveBeenCalledWith(
                    expect.objectContaining({
                        query: "test",
                    }),
                    undefined,
                );
            });
        });
    });

    describe("Single Search Domain", () => {
        beforeEach(() => {
            MOCK_SEARCH_SOURCE.addDomain(MOCK_SEARCH_DOMAIN);
            SearchService.addSource(MOCK_SEARCH_SOURCE);

            return act(async () => {
                result = render(<MockSearchPage />);
            });
        });
        it("does not render radio buttons", async () => {
            const radioGroup = result.queryByRole("radiogroup");
            expect(radioGroup).toBeNull();
        });
    });

    describe("Multiple Search Domains", () => {
        describe("With one isolated domain and one non-isolated domain", () => {
            beforeEach(async () => {
                MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.addDomain(DISCUSSIONS_SEARCH_DOMAIN);
                MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.addDomain(MEMBERS_SEARCH_DOMAIN);

                SearchService.addSource(MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS);

                return act(async () => {
                    result = render(<MockSearchPage />);
                });
            });
            it("renders radio buttons for each domain", async () => {
                const radioGroup = await result.findByRole("radiogroup");
                expect(
                    await within(radioGroup).findByRole("radio", { name: LOADABLE_DISCUSSIONS_SEARCH_DOMAIN.name }),
                ).toBeInTheDocument();
                expect(
                    await within(radioGroup).findByRole("radio", { name: LOADABLE_MEMBERS_SEARCH_DOMAIN.name }),
                ).toBeInTheDocument();
            });
            it("does not render an All button", async () => {
                const radioGroup = await result.findByRole("radiogroup");
                expect(within(radioGroup).queryByRole("radio", { name: "All" })).not.toBeInTheDocument();
            });
        });

        describe("With multiple non-isolated domains", () => {
            beforeEach(async () => {
                MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.addDomain(PLACES_SEARCH_DOMAIN);
                MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.addDomain(MOCK_ASYNC_SEARCH_DOMAIN);
                SearchService.addSource(MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS);
            });
            it("renders an All button", async () => {
                await act(async () => {
                    result = render(
                        <MockSearchPage
                            initialFormState={{
                                domain: PLACES_SEARCH_DOMAIN.key,
                            }}
                        />,
                    );
                });
                const radioGroup = await result.findByRole("radiogroup");
                expect(await within(radioGroup).findByRole("radio", { name: "All" })).toBeInTheDocument();
            });

            it("renders a radio button for each loadable domain", async () => {
                result = render(
                    <MockSearchPage
                        initialFormState={{
                            domain: PLACES_SEARCH_DOMAIN.key,
                        }}
                    />,
                );
                const radioGroup = await result.findByRole("radiogroup");
                expect(
                    await within(radioGroup).findByRole("radio", { name: LOADABLE_PLACES_SEARCH_DOMAIN.name }),
                ).toBeInTheDocument();
                expect(
                    await within(radioGroup).findByRole("radio", { name: MOCK_ASYNC_SEARCH_DOMAIN_LOADABLE.name }),
                ).toBeInTheDocument();
            });

            describe("Changing SearchDomain", () => {
                const fakePlacesTypes = ["fakeType1", "fakeType2"];
                const fakeQuery = "test";

                beforeEach(async () => {
                    await act(async () => {
                        PlacesSearchTypeFilter.addSearchTypes({
                            label: "Fake Place Types",
                            values: fakePlacesTypes,
                        });

                        result = render(
                            <MockSearchPage
                                initialFormState={
                                    {
                                        domain: PLACES_SEARCH_DOMAIN.key,
                                        query: fakeQuery,
                                        types: fakePlacesTypes,
                                    } as ISearchForm<{ types: string[] }>
                                }
                            />,
                        );

                        const form = await result.findByRole("search");
                        fireEvent.submit(form);
                    });
                });

                afterEach(() => {
                    PlacesSearchTypeFilter.searchTypes = [];
                });

                it("Clears unsupported form values", async () => {
                    const radioGroup = await result.findByRole("radiogroup");
                    const asyncDomainRadioBtn = await within(radioGroup).findByRole("radio", {
                        name: MOCK_ASYNC_SEARCH_DOMAIN_LOADABLE.name,
                    });

                    // The initial search contains `types`.
                    expect(MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.performSearch).toHaveBeenLastCalledWith(
                        expect.objectContaining({
                            query: fakeQuery,
                            types: fakePlacesTypes,
                        }),
                        undefined,
                    );

                    // Change to a domain that doesn't support `types`.
                    fireEvent.click(asyncDomainRadioBtn);

                    // The search in the new domain was performed without `types`.
                    expect(MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.performSearch).toHaveBeenLastCalledWith(
                        expect.not.objectContaining({
                            types: fakePlacesTypes,
                        }),
                        undefined,
                    );

                    // The search in the new domain was performed with the same `query` value.
                    expect(MOCK_SEARCH_SOURCE_WITH_ASYNC_DOMAINS.performSearch).toHaveBeenLastCalledWith(
                        expect.objectContaining({
                            query: fakeQuery,
                        }),
                        undefined,
                    );
                });
            });
        });
    });

    describe("Submitting search form", () => {
        beforeEach(() => {
            MOCK_SEARCH_SOURCE.addDomain(MOCK_SEARCH_DOMAIN);
            SearchService.addSource(MOCK_SEARCH_SOURCE);

            return act(async () => {
                result = render(
                    <SiteSectionContext.Provider
                        value={{
                            siteSection: mockSiteSection,
                        }}
                    >
                        <MockSearchPage />
                    </SiteSectionContext.Provider>,
                );

                const form = await result.findByRole("search");
                fireEvent.submit(form);
            });
        });

        it("calls the SearchDomain's transformFormToQuery implementation", async () => {
            expect(MOCK_SEARCH_DOMAIN.transformFormToQuery).toHaveBeenCalled();
        });

        it("Includes the siteSectionID in the search query", async () => {
            expect(MOCK_SEARCH_SOURCE.performSearch).toHaveBeenCalledWith(
                expect.objectContaining({
                    siteSectionID: mockSiteSection.sectionID,
                }),
                undefined,
            );
        });
    });
});
