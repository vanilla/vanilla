/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import DiscussionListItem from "@library/features/discussions/DiscussionListItem";
import { IWidgetCommonProps } from "@library/homeWidget/HomeWidget";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import {
    homeWidgetContainerClasses,
    IHomeWidgetContainerOptions,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { homeWidgetItemClasses } from "@library/homeWidget/HomeWidgetItem.styles";
import { PageBox } from "@library/layout/PageBox";
import React from "react";

interface IProps extends React.ComponentProps<typeof DiscussionListView>, IWidgetCommonProps {
    containerOptions?: IHomeWidgetContainerOptions;
    assetHeader?: React.ReactNode;
}

export function DiscussionGridView(props: IProps) {
    const { discussions, discussionOptions, disableButtonsInItems, containerOptions, title, subtitle, description } =
        props;
    const containerClasses = homeWidgetContainerClasses(containerOptions);

    //when discussions don't fill all the container space we need to fill that space with extra spacers so the flexBox do't break, just like we do in HomeWidget
    let extraSpacerItemCount = 0;
    if (discussions && discussions.length < (containerOptions?.maxColumnCount ?? 3)) {
        extraSpacerItemCount = (containerOptions?.maxColumnCount ?? 3) - discussions.length;
    }

    //metas for grid/carousel are predefined and always rendered as icons
    const defaultDiscussionOptions = {
        ...discussionOptions,
        metas: {
            ...discussionOptions?.metas,
            asIcons: true,
            display: {
                ...discussionOptions?.metas?.display,
                category: false,
                startedByUser: false,
                lastUser: discussionOptions?.metas?.display?.lastUser,
                lastCommentDate: false,
                viewCount: discussionOptions?.metas?.display?.viewCount,
                commentCount: discussionOptions?.metas?.display?.commentCount,
                score: discussionOptions?.metas?.display?.score,
                userTags: false,
                unreadCount: false,
            },
        },
    };

    return (
        <HomeWidgetContainer
            title={title}
            subtitle={subtitle}
            description={description}
            options={{
                ...containerOptions,
            }}
            extraHeader={props.assetHeader}
        >
            {discussions &&
                discussions.map((discussion) => {
                    return (
                        <DiscussionListItem
                            noCheckboxes={props.noCheckboxes}
                            discussion={discussion}
                            key={discussion.discussionID}
                            className={homeWidgetItemClasses().root}
                            discussionOptions={defaultDiscussionOptions}
                            asTile
                            disableButtonsInItems={disableButtonsInItems}
                        ></DiscussionListItem>
                    );
                })}
            {[...new Array(extraSpacerItemCount)].map((_, i) => {
                return <div key={"spacer-" + i} className={containerClasses.gridItemSpacer}></div>;
            })}
        </HomeWidgetContainer>
    );
}
