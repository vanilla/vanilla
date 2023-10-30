/**
 * @author Mihran Abrahamian <mabrahamian@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { render, act, cleanup, fireEvent, within, RenderResult } from "@testing-library/react";
import { SearchFormContextProvider } from "./SearchFormContextProvider";
import { SearchPageContent } from "./SearchPage";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import COMMUNITY_SEARCH_SOURCE from "./CommunitySearchSource";
import { SearchService } from "./SearchService";
import { SearchSourcesContextProvider } from "./SearchSourcesContextProvider";
import DISCUSSIONS_SEARCH_DOMAIN from "@vanilla/addon-vanilla/search/DiscussionsSearchDomain";
import { MOCK_SEARCH_DOMAIN, MockSearchSource } from "./__fixtures__/Search.fixture";
import MEMBERS_SEARCH_DOMAIN from "@dashboard/components/panels/MembersSearchDomain";
import PLACES_SEARCH_DOMAIN from "@dashboard/components/panels/PlacesSearchDomain";
import { LiveAnnouncer } from "react-aria-live";
import { setMeta } from "@library/utility/appUtils";

const MOCK_SEARCH_SOURCE = new MockSearchSource();

async function renderSearchPage() {
    return render(
        <SearchSourcesContextProvider>
            <PermissionsFixtures.AllPermissions>
                <LiveAnnouncer>
                    <SearchFormContextProvider>
                        <SearchPageContent />
                    </SearchFormContextProvider>
                </LiveAnnouncer>
            </PermissionsFixtures.AllPermissions>
        </SearchSourcesContextProvider>,
    );
}

afterEach(() => {
    cleanup();
    SearchService.sources = [];
    COMMUNITY_SEARCH_SOURCE.domains = [];
    MOCK_SEARCH_SOURCE.domains = [];
    MOCK_SEARCH_SOURCE.performSearch.mockClear();
    MOCK_SEARCH_DOMAIN.transformFormToQuery.mockClear();
});

describe("SearchPage", () => {
    let result: RenderResult;

    describe("Single Search Source", () => {
        it("does not render tabs", async () => {
            SearchService.addSource(COMMUNITY_SEARCH_SOURCE);

            await act(async () => {
                result = await renderSearchPage();
            });

            const tabs = result.queryAllByRole("tab");
            expect(tabs).toHaveLength(0);
        });
    });
    describe("Multiple Search Sources", () => {
        beforeEach(() => {
            COMMUNITY_SEARCH_SOURCE.addDomain(DISCUSSIONS_SEARCH_DOMAIN);
            SearchService.addSource(COMMUNITY_SEARCH_SOURCE);
            MOCK_SEARCH_SOURCE.addDomain(MOCK_SEARCH_DOMAIN);
            SearchService.addSource(MOCK_SEARCH_SOURCE);
        });

        it("it renders multiple tabs", async () => {
            await act(async () => {
                result = await renderSearchPage();
            });

            const tabs = await result.findAllByRole("tab");
            expect(tabs).toHaveLength(2);
            const tabOneLabel = await within(tabs[0]).findByText(COMMUNITY_SEARCH_SOURCE.label);
            expect(tabOneLabel).toBeInTheDocument();

            const tabTwoLabel = await within(tabs[1]).findByText(MOCK_SEARCH_SOURCE.label);
            expect(tabTwoLabel).toBeInTheDocument();
        });

        describe("Changing SearchSource", () => {
            it("performs a search on the newly selected SearchSource", async () => {
                await act(async () => {
                    result = await renderSearchPage();
                });

                const tabTwo = await result.findByRole("tab", { name: MOCK_SEARCH_SOURCE.label });

                await act(async () => {
                    fireEvent.click(tabTwo);
                });

                expect(MOCK_SEARCH_SOURCE.performSearch).toHaveBeenCalledTimes(1);
            });
        });
    });

    describe("Single Search Domain", () => {
        beforeEach(() => {
            MOCK_SEARCH_SOURCE.addDomain(MOCK_SEARCH_DOMAIN);
            SearchService.addSource(MOCK_SEARCH_SOURCE);

            return act(async () => {
                result = await renderSearchPage();
            });
        });
        it("does not render radio buttons", async () => {
            const radioGroup = result.queryByRole("radiogroup");
            expect(radioGroup).toBeNull();
        });
    });

    describe("Multiple Search Domains", () => {
        describe("With one non-isolated domain", () => {
            beforeEach(() => {
                COMMUNITY_SEARCH_SOURCE.addDomain(DISCUSSIONS_SEARCH_DOMAIN);
                COMMUNITY_SEARCH_SOURCE.addDomain(MEMBERS_SEARCH_DOMAIN);
                SearchService.addSource(COMMUNITY_SEARCH_SOURCE);

                return act(async () => {
                    result = await renderSearchPage();
                });
            });
            it("renders radio buttons for each domain", async () => {
                const radioGroup = await result.findByRole("radiogroup");
                expect(
                    await within(radioGroup).findByRole("radio", { name: DISCUSSIONS_SEARCH_DOMAIN.name }),
                ).toBeInTheDocument();
                expect(
                    await within(radioGroup).findByRole("radio", { name: MEMBERS_SEARCH_DOMAIN.name }),
                ).toBeInTheDocument();
            });
            it("does not render an All button", async () => {
                const radioGroup = await result.findByRole("radiogroup");
                expect(within(radioGroup).queryByRole("radio", { name: "All" })).not.toBeInTheDocument();
            });
        });

        describe("With multiple non-isolated domains", () => {
            beforeEach(() => {
                COMMUNITY_SEARCH_SOURCE.addDomain(DISCUSSIONS_SEARCH_DOMAIN);
                COMMUNITY_SEARCH_SOURCE.addDomain(PLACES_SEARCH_DOMAIN);
                COMMUNITY_SEARCH_SOURCE.addDomain(MEMBERS_SEARCH_DOMAIN);
                SearchService.addSource(COMMUNITY_SEARCH_SOURCE);

                return act(async () => {
                    result = await renderSearchPage();
                });
            });
            it("renders an All button", async () => {
                const radioGroup = await result.findByRole("radiogroup");
                expect(await within(radioGroup).findByRole("radio", { name: "All" })).toBeInTheDocument();
            });

            describe("Changing SearchDomain", () => {
                // TODO: https://higherlogic.atlassian.net/browse/VNLA-4624
                // set up a search with some mock query parameters in domain A
                // switch to domain B
                // assert: any parameters unsupported in domain B are cleared from the query.
            });
        });
    });

    describe("Submitting search form", () => {
        beforeEach(() => {
            MOCK_SEARCH_SOURCE.addDomain(MOCK_SEARCH_DOMAIN);
            SearchService.addSource(MOCK_SEARCH_SOURCE);

            return act(async () => {
                result = await renderSearchPage();
                const form = await result.findByRole("search");
                fireEvent.submit(form);
            });
        });

        it("calls the SearchDomain's transformFormToQuery implementation", async () => {
            expect(MOCK_SEARCH_DOMAIN.transformFormToQuery).toHaveBeenCalledTimes(1);
        });

        it("calls the SearchSource's performSearch implementation", async () => {
            expect(MOCK_SEARCH_SOURCE.performSearch).toHaveBeenCalledTimes(1);
        });
    });
});
