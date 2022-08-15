/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionList } from "@library/features/discussions/DiscussionList";
import { IWidgetCommonProps } from "@library/homeWidget/HomeWidget";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import React from "react";

interface IProps extends React.ComponentProps<typeof DiscussionList>, IWidgetCommonProps {
    viewAllUrl?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    disableButtonsInItems?: boolean; // when rendering in widget preview/overview we don't want them to be interactive
}

export function DiscussionListModule(props: IProps) {
    const { title, description, subtitle, viewAllUrl, ...rest } = props;

    return (
        <HomeWidgetContainer
            title={title}
            subtitle={subtitle}
            description={description}
            options={{
                ...props.containerOptions,
                isGrid: false,
                viewAll: {
                    ...props.containerOptions?.viewAll,
                    to: viewAllUrl ?? props.containerOptions?.viewAll?.to,
                },
            }}
        >
            <DiscussionList {...rest} />
        </HomeWidgetContainer>
    );
}

export default DiscussionListModule;
