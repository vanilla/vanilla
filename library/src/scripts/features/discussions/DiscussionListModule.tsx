/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionList } from "@library/features/discussions/DiscussionList";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import React, { useEffect } from "react";
import { useSelector } from "react-redux";

interface IProps extends React.ComponentProps<typeof DiscussionList> {
    title?: string;
    description?: string;
    subtitle?: string;
    viewAllUrl?: string;
}

export function DiscussionListModule(props: IProps) {
    const { title, description, subtitle, viewAllUrl, ...rest } = props;

    return (
        <HomeWidgetContainer
            title={title}
            subtitle={subtitle}
            options={{
                isGrid: false,
                description,
                viewAll: {
                    to: viewAllUrl,
                },
            }}
        >
            <DiscussionList {...rest} />
        </HomeWidgetContainer>
    );
}
