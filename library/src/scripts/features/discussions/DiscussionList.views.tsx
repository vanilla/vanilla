/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import DiscussionListItem from "@library/features/discussions/DiscussionListItem";
import { List } from "@library/lists/List";
import React from "react";

interface IProps {
    discussions: IDiscussion[];
}

export function DiscussionList(props: IProps) {
    const { discussions } = props;
    const variables = discussionListVariables();
    return (
        <List
            options={{
                box: variables.contentBoxes.depth1,
                itemBox: variables.contentBoxes.depth2,
            }}
            {...props}
        >
            {discussions.map((discussion) => {
                return <DiscussionListItem discussion={discussion} key={discussion.discussionID}></DiscussionListItem>;
            })}
        </List>
    );
}
