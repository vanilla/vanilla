/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import IndependentSearch from "@library/features/search/IndependentSearch";
import SearchContext from "@library/contexts/SearchContext";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { MemoryRouter } from "react-router";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
import { PublishStatus } from "@library/@types/api/core";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import { globalVariables } from "@library/styles/globalStyleVars";
import { bannerClasses } from "@library/banner/Banner.styles";
import {
    TypeAllIcon,
    TypeArticlesIcon,
    TypeCategoriesAndGroupsIcon,
    TypeCategoriesIcon,
    TypeDiscussionsIcon,
    TypeIdeasIcon,
    TypeMemberIcon,
    TypePollsIcon,
    TypeQuestionIcon,
    TypeGroupsIcon,
    TypeKnowledgeBaseIcon,
} from "@library/icons/searchIcons";
import { t } from "@vanilla/i18n/src";
import { StoryContent } from "@library/storybook/StoryContent";
import { useSection } from "@library/layout/LayoutContext";
import classNames from "classnames";
import { PlacesResultMeta } from "@dashboard/components/panels/registerPlaceSearchDomain";

const story = storiesOf("Search", module);

const dummyUserFragment = {
    userID: 1,
    name: "Joe",
    photoUrl: "",
    dateLastActive: "2016-07-25 17:51:15",
};

story.add("Search Box", () => {
    const classesSearch = bannerClasses();
    return (
        <StoryContent>
            <StoryHeading depth={1}>Search Box</StoryHeading>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <MemoryRouter>
                    <div
                        style={{
                            backgroundColor: globalVariables().mixBgAndFg(0.5).toHexString(),
                            padding: `30px 10px`,
                        }}
                    >
                        <div className={classesSearch.searchContainer}>
                            <IndependentSearch
                                buttonClass={classesSearch.searchButton}
                                buttonType={ButtonTypes.CUSTOM}
                                isLarge={true}
                                placeholder={t("Search")}
                                iconClass={classesSearch.icon}
                                contentClass={classesSearch.content}
                            />
                        </div>
                    </div>
                </MemoryRouter>
            </SearchContext.Provider>
        </StoryContent>
    );
});

story.add("Search Results", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Search Results</StoryHeading>
            <ResultList
                results={[
                    {
                        name: "External Result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                crumbs={[{ name: "Site Node 1" }]}
                                type={"Discussion"}
                                isForeign={true}
                            />
                        ),
                        // attachments: [{ name: "My File", type: AttachmentType.WORD }],
                        icon: <TypeDiscussionsIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        // attachments: [{ name: "My File", type: AttachmentType.WORD }],
                        icon: <TypeDiscussionsIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        image: "https://upload.wikimedia.org/wikipedia/en/7/70/Bob_at_Easel.jpg",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeQuestionIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",

                        icon: <TypePollsIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeIdeasIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeCategoriesIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeMemberIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeCategoriesAndGroupsIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeArticlesIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeAllIcon />,
                    },
                    {
                        name: "Example search result - no icon",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                    },
                ]}
            />
            <StoryHeading>Category result (used on categories page)</StoryHeading>
            <ResultList
                results={[
                    {
                        name: "Example category result",
                        headingLevel: 3,
                        url: "#",
                        meta: <ResultMeta dateUpdated={"2016-07-25 17:51:15"} updateUser={dummyUserFragment} />,
                    },
                ]}
            />
        </StoryContent>
    );
});

story.add("Search Results for Places", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}> Search Results for Places</StoryHeading>
            <ResultList
                results={[
                    {
                        name: "Categories Search Result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <PlacesResultMeta
                                searchResult={{
                                    type: t("Categories"),
                                    counts: [
                                        { labelCode: "sub-categories", count: 3 },
                                        { labelCode: "discussions", count: 30 },
                                    ],
                                }}
                            />
                        ),
                        icon: <TypeCategoriesIcon />,
                    },
                    {
                        name: "Knowledge Bases Search Result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <PlacesResultMeta
                                searchResult={{
                                    type: t("Knowledge Base"),
                                    counts: [{ labelCode: "articles", count: 1201 }],
                                }}
                            />
                        ),
                        icon: <TypeKnowledgeBaseIcon />,
                    },
                    {
                        name: "Groups Search Result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <PlacesResultMeta
                                searchResult={{
                                    type: t("Groups"),
                                    counts: [
                                        { labelCode: "members", count: 5002 },
                                        { labelCode: "discussions", count: 123 },
                                    ],
                                }}
                            />
                        ),
                        icon: <TypeGroupsIcon />,
                    },
                    {
                        name: "Categories Search Result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <PlacesResultMeta
                                searchResult={{
                                    type: t("Categories"),
                                    counts: [
                                        { labelCode: "sub-categories", count: 1 },
                                        { labelCode: "discussions", count: 0 },
                                    ],
                                }}
                            />
                        ),
                        icon: <TypeCategoriesIcon />,
                    },
                    {
                        name: "Knowledge Bases Search Result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <PlacesResultMeta
                                searchResult={{
                                    type: t("Knowledge Base"),
                                    counts: [{ labelCode: "articles", count: 1 }],
                                }}
                            />
                        ),
                        icon: <TypeKnowledgeBaseIcon />,
                    },
                    {
                        name: "Groups Search Result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <PlacesResultMeta
                                searchResult={{
                                    type: t("Groups"),
                                    counts: [
                                        { labelCode: "members", count: 1 },
                                        { labelCode: "discussions", count: 1 },
                                    ],
                                }}
                            />
                        ),
                        icon: <TypeGroupsIcon />,
                    },
                ]}
            />
        </StoryContent>
    );
});
