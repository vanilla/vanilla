/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { LoadStatus } from "@library/@types/api/core";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { useDiscussionList } from "@library/features/discussions/discussionHooks";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import DiscussionListLoader from "@library/features/discussions/DiscussionListLoader";
import { LegacyDiscussionListSelectAll } from "@library/features/discussions/DiscussionListSelectAll";
import React from "react";

interface IProps extends Partial<React.ComponentProps<typeof DiscussionListView>> {
    apiParams: IGetDiscussionListParams;
    isMainContent?: boolean;
    isAsset?: boolean;
}

export function DiscussionList(props: IProps) {
    const discussions = useDiscussionList(props.apiParams, props.discussions);

    if (discussions.status === LoadStatus.LOADING || discussions.status === LoadStatus.PENDING) {
        return <DiscussionListLoader count={20} itemOptions={{ excerpt: true, checkbox: !props.noCheckboxes }} />;
    }

    if (!discussions.data?.discussionList || discussions.status === LoadStatus.ERROR || discussions.error) {
        return <CoreErrorMessages apiError={discussions.error} />;
    }

    return (
        <>
            <DiscussionListView
                noCheckboxes={props.noCheckboxes}
                discussions={discussions.data.discussionList}
                discussionOptions={{
                    ...props.discussionOptions,
                    featuredImage: {
                        display: props.apiParams.featuredImage,
                        ...(props.apiParams.fallbackImage && { fallbackImage: props.apiParams.fallbackImage }),
                    },
                }}
                disableButtonsInItems={props.disableButtonsInItems}
            />
            {props.isMainContent && !props.noCheckboxes && (
                <LegacyDiscussionListSelectAll
                    discussionIDs={discussions.data?.discussionList.map((discussion) => discussion.discussionID)}
                />
            )}
        </>
    );
}
