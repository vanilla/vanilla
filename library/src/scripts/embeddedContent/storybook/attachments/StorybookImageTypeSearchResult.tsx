import { StoryHeading } from "@library/storybook/StoryHeading";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
import { PublishStatus } from "@library/@types/api/core";
import { TypeDiscussionsIcon } from "@library/icons/searchIcons";
import React from "react";
import Result from "@library/result/Result";

const dummyUserFragment = {
    userID: 1,
    name: "Joe",
    photoUrl: "",
    dateLastActive: "2016-07-25 17:51:15",
};

export function StoryBookImageTypeSearchResult(props: {
    type: "square" | "flush" | "tall" | "wide";
    imageSet: {
        big: string;
        medium: string;
        small: string;
    };
}) {
    let ratio = "1x1";
    switch (props.type) {
        case "flush":
            ratio = "16x9";
            break;
        case "tall":
            ratio = "5x20";
            break;
        case "wide":
            ratio = "20x5";
            break;
    }
    return (
        <>
            <StoryHeading>{`Image type: "${props.type}" with ratio (${ratio})`}</StoryHeading>
            <ResultList
                result={Result}
                results={[
                    {
                        name: `Example search result - Big Image {"${props.type}"} with ratio (${ratio})`,
                        url: "#",
                        image: props.imageSet.big,
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
                        icon: <TypeDiscussionsIcon />,
                    },
                    {
                        name: `Example search result - Flush Image {"${props.type}"} with ratio (${ratio})`,
                        url: "#",
                        image: props.imageSet.medium,
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
                        icon: <TypeDiscussionsIcon />,
                    },
                    {
                        name: `Example search result - Small Image {"${props.type}"} with ratio (${ratio})`,
                        url: "#",
                        image: props.imageSet.small,
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
                        icon: <TypeDiscussionsIcon />,
                    },
                    {
                        name: `Example search result - icon, no image`,
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
                        icon: <TypeDiscussionsIcon />,
                    },
                    {
                        name: `Example search result - Image, no icon`,
                        url: "#",
                        image: props.imageSet.small,
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
                    {
                        name: `Example search result - no Icon, no image`,
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
        </>
    );
}
