/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITag } from "@library/features/tags/TagsReducer";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { Metas, MetaTag } from "@library/metas/Metas";
import { tagCloudVariables, TagPreset } from "@library/metas/Tags.variables";
import { getMeta } from "@library/utility/appUtils";
import { tagWidgetClasses } from "@vanilla/addon-vanilla/tag/TagWidget.classes";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import React from "react";

interface IProps {
    tags: ITag[];
    title?: string;
    subtitle?: string;
    description?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    itemOptions?: IItemOptions;
}

interface IItemOptions {
    tagPreset: TagPreset;
}

export default function TagWidget(props: IProps) {
    const { subtitle, description, tags, containerOptions, itemOptions } = props;
    const title = props.title ?? t("Popular Tags");
    const classes = tagWidgetClasses(containerOptions);
    const vars = tagCloudVariables();
    const customLayoutsForDiscussionListIsEnabled = getMeta("featureFlags.customLayout.discussionList.Enabled", false);

    return (
        <HomeWidgetContainer title={title} subtitle={subtitle} description={description} options={containerOptions}>
            <div className={classNames(classes.root)}>
                <Metas>
                    {tags &&
                        tags.map((tag, index) => {
                            return (
                                <MetaTag
                                    key={index}
                                    to={
                                        customLayoutsForDiscussionListIsEnabled
                                            ? `/discussions?tagID=${tag.tagID}`
                                            : `/discussions/tagged/${tag.urlcode}`
                                    }
                                    tagPreset={itemOptions?.tagPreset ?? vars.tagPreset}
                                >
                                    {tag.name}
                                    {vars.showCount && (
                                        <span className={classNames(classes.count)}> {tag.countDiscussions}</span>
                                    )}
                                </MetaTag>
                            );
                        })}
                </Metas>
            </div>
        </HomeWidgetContainer>
    );
}
