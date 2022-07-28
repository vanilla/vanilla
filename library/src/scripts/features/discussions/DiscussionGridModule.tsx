/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { useDiscussionList } from "@library/features/discussions/discussionHooks";
import DiscussionListItem from "@library/features/discussions/DiscussionListItem";
import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { homeWidgetContainerClasses, WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { homeWidgetItemClasses } from "@library/homeWidget/HomeWidgetItem.styles";
import Loader from "@library/loaders/Loader";
import React from "react";

interface IProps extends React.ComponentProps<typeof DiscussionListModule> {}

export function DiscussionGridModule(props: IProps) {
    const { title, subtitle, description, containerOptions, discussionOptions, disableButtonsInItems } = props;
    const discussions = useDiscussionList(props.apiParams, props.discussions);
    const containerClasses = homeWidgetContainerClasses(containerOptions);

    //when discussions don't fill all the container space we need to fill that space with extra spacers so the flexBox do't break, just like we do in HomeWidget
    let extraSpacerItemCount = 0;
    if (
        discussions.status === LoadStatus.SUCCESS &&
        discussions?.data &&
        containerOptions?.displayType === WidgetContainerDisplayType.GRID &&
        discussions?.data.length < (containerOptions.maxColumnCount ?? 3)
    ) {
        extraSpacerItemCount = (containerOptions.maxColumnCount ?? 3) - discussions.data.length;
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

    if (discussions.status === LoadStatus.LOADING || discussions.status === LoadStatus.PENDING) {
        // Until we have a proper skeleton.
        return <Loader />;
    }

    if (!discussions.data || discussions.status === LoadStatus.ERROR || discussions.error) {
        return <CoreErrorMessages apiError={discussions.error} />;
    }

    return (
        <HomeWidgetContainer
            title={title}
            subtitle={subtitle}
            description={description}
            options={{
                ...containerOptions,
            }}
        >
            {discussions.data.map((discussion) => {
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
