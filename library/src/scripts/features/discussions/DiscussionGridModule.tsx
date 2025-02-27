/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { DiscussionGridView } from "@library/features/discussions/DiscussionGridView";
import { useDiscussionList } from "@library/features/discussions/discussionHooks";
import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import Loader from "@library/loaders/Loader";
import React from "react";

interface IProps extends React.ComponentProps<typeof DiscussionListModule> {}

export function DiscussionGridModule(props: IProps) {
    const discussions = useDiscussionList(props.apiParams, props.discussions);

    if (discussions.status === LoadStatus.LOADING || discussions.status === LoadStatus.PENDING) {
        // Until we have a proper skeleton.
        return <Loader />;
    }

    if (!discussions.data?.discussionList || discussions.status === LoadStatus.ERROR || discussions.error) {
        return <CoreErrorMessages apiError={discussions.error} />;
    }

    return <DiscussionGridView discussions={discussions.data.discussionList} {...props} />;
}
