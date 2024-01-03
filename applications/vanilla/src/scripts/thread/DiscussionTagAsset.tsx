/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { ITag } from "@library/features/tags/TagsReducer";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { Widget } from "@library/layout/Widget";
import { MetaTag, Metas } from "@library/metas/Metas";
import { getMeta } from "@library/utility/appUtils";
import qs from "qs";
import React from "react";

interface IProps {
    title?: string;
    subtitle?: string;
    description?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    tags: ITag[];
}

/**
 * Display given discussion tags as tokens with links to search
 */
export default function DiscussionTagAsset(props: IProps) {
    const { title, subtitle, description, containerOptions, tags } = props;

    const variables = discussionListVariables();

    const customLayoutsForDiscussionListIsEnabled = getMeta("featureFlags.customLayout.discussionList.Enabled", false);

    if (tags?.length < 1) {
        return <></>;
    }

    return (
        <Widget>
            <HomeWidgetContainer
                subtitle={subtitle}
                description={description}
                options={containerOptions}
                title={title}
                depth={3}
            >
                <Metas>
                    {tags.map((tag) => {
                        const query = qs.stringify({
                            domain: "discussions",
                            tagsOptions: [
                                {
                                    value: tag.tagID,
                                    label: tag.name,
                                    tagCode: tag.urlcode,
                                },
                            ],
                        });
                        const searchUrl = `/search?${query}`;
                        return (
                            <MetaTag
                                to={
                                    customLayoutsForDiscussionListIsEnabled
                                        ? `/discussions?tagID=${tag.tagID}`
                                        : searchUrl
                                }
                                key={tag.tagID}
                                tagPreset={variables.userTags.tagPreset}
                            >
                                {tag.name}
                            </MetaTag>
                        );
                    })}
                </Metas>
            </HomeWidgetContainer>
        </Widget>
    );
}
