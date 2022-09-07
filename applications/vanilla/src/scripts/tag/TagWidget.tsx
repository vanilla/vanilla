/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { ITag } from "@library/features/tags/TagsReducer";
import { Metas, MetaTag } from "@library/metas/Metas";
import { TagPreset } from "@library/metas/Tags.variables";
import { tagWidgetClasses } from "@vanilla/addon-vanilla/tag/TagWidget.classes";
import { Tag } from "@library/metas/Tags";

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

    return (
        <HomeWidgetContainer title={title} subtitle={subtitle} description={description} options={containerOptions}>
            <div className={classNames(classes.root)}>
                <Metas>
                    {tags &&
                        tags.map((tag, index) => {
                            return (
                                <MetaTag
                                    key={index}
                                    to={`/discussions/tagged/${tag.urlcode}`}
                                    preset={itemOptions?.tagPreset ?? TagPreset.STANDARD}
                                >
                                    {tag.name} <span className={classNames(classes.count)}>{tag.countDiscussions}</span>
                                </MetaTag>
                            );
                        })}
                </Metas>
            </div>
        </HomeWidgetContainer>
    );
}
