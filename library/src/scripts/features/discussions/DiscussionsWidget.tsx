/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionList } from "@library/features/discussions/DiscussionList";
import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import { DiscussionGridModule } from "@library/features/discussions/DiscussionGridModule";

import { IWidgetCommonProps } from "@library/homeWidget/HomeWidget";
import {
    IHomeWidgetContainerOptions,
    WidgetContainerDisplayType,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import React from "react";
import { QuickLinks } from "@library/navigation/QuickLinks";

interface IProps extends React.ComponentProps<typeof DiscussionListModule> {}

export function DiscussionsWidget(props: IProps) {
    const { containerOptions } = props;
    const isList = !containerOptions?.displayType || containerOptions.displayType === WidgetContainerDisplayType.LIST;
    const isLink = containerOptions?.displayType === WidgetContainerDisplayType.LINK;

    const discussionLinks = isLink
        ? props.discussions?.map((discussion, index) => {
              return {
                  id: `${index}`,
                  name: discussion.name ?? "",
                  url: discussion.url ?? "",
              };
          })
        : [];

    return isList ? (
        <DiscussionListModule
            {...props}
            containerOptions={{ ...containerOptions, displayType: WidgetContainerDisplayType.LIST }}
        />
    ) : isLink ? (
        <QuickLinks title={props.title} links={discussionLinks} containerOptions={props.containerOptions} />
    ) : (
        <DiscussionGridModule {...props} />
    );
}

export default DiscussionsWidget;
