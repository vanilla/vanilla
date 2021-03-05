/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionList } from "@library/features/discussions/DiscussionList.views";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import React from "react";

interface IProps extends React.ComponentProps<typeof DiscussionList> {
    title?: string;
    description?: string;
    subtitle?: string;
    viewAllUrl?: string;
}

export function DiscussionsListModule(props: IProps) {
    const { title, description, subtitle, viewAllUrl, ...rest } = props;

    return (
        <HomeWidgetContainer
            title={title}
            options={{
                isGrid: false,
                subtitle: {
                    content: subtitle,
                },
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
