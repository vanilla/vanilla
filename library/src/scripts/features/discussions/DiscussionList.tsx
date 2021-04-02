/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { useDiscussionList } from "@library/features/discussions/discussionHooks";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import Loader from "@library/loaders/Loader";
import React from "react";

interface IProps {
    apiParams: IGetDiscussionListParams;
    discussions?: IDiscussion[];
}

export function DiscussionList(props: IProps) {
    const discussions = useDiscussionList(props.apiParams, props.discussions);

    if (discussions.status === LoadStatus.LOADING || discussions.status === LoadStatus.PENDING) {
        // Until we have a proper skeleton.
        return <Loader />;
    }

    if (!discussions.data || discussions.status === LoadStatus.ERROR || discussions.error) {
        return <CoreErrorMessages apiError={discussions.error} />;
    }

    return <DiscussionListView discussions={discussions.data} />;
}
