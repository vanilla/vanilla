/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionListModule } from "@library/features/discussions/DiscussionListModule";
import { DiscussionGridModule } from "@library/features/discussions/DiscussionGridModule";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import React, { useMemo } from "react";
import { QuickLinks } from "@library/navigation/QuickLinks";
import DiscussionListAsset from "@library/features/discussions/DiscussionListAsset";

interface IProps extends React.ComponentProps<typeof DiscussionListModule> {}

export function DiscussionsWidget(props: IProps) {
    const { containerOptions } = props;
    const isList = !containerOptions?.displayType || containerOptions.displayType === WidgetContainerDisplayType.LIST;
    const isLink = containerOptions?.displayType === WidgetContainerDisplayType.LINK;

    const discussionOptions = useMemo(
        () => ({
            ...props.discussionOptions,
            featuredImage: {
                display: props.apiParams.featuredImage,
                fallbackImage: props.apiParams.fallbackImage,
            },
        }),
        [props],
    );

    const discussionLinks = isLink
        ? props.discussions?.map((discussion, index) => {
              return {
                  id: `${index}`,
                  name: discussion.name ?? "",
                  url: discussion.url ?? "",
              };
          })
        : [];

    if (props.isAsset) {
        return <DiscussionListAsset {...props} discussionOptions={discussionOptions} isList={isList} />;
    }

    return isList ? (
        <DiscussionListModule
            {...props}
            discussionOptions={discussionOptions}
            containerOptions={{ ...containerOptions, displayType: WidgetContainerDisplayType.LIST }}
        />
    ) : isLink ? (
        <QuickLinks title={props.title} links={discussionLinks} containerOptions={props.containerOptions} />
    ) : (
        <DiscussionGridModule {...props} discussionOptions={discussionOptions} />
    );
}

export default DiscussionsWidget;
