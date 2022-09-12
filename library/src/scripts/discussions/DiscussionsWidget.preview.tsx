/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import { DiscussionsWidget } from "@library/features/discussions/DiscussionsWidget";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { IDiscussion } from "@dashboard/@types/api/discussion";

interface IProps extends Omit<React.ComponentProps<typeof DiscussionsWidget>, "discussions"> {
    discussions?: IDiscussion[];
}

export function DiscussionsWidgetPreview(props: IProps) {
    const { discussions: discussionsFromProps, apiParams, containerOptions, discussionOptions } = props;

    const { limit } = apiParams;

    const includeUnread =
        (!containerOptions?.displayType || containerOptions.displayType === WidgetContainerDisplayType.LIST) &&
        discussionOptions?.metas?.display?.unreadCount;

    const discussions = useMemo(() => {
        return discussionsFromProps ?? LayoutEditorPreviewData.discussions(parseInt(`${limit ?? 10}`), includeUnread);
    }, [includeUnread, limit, discussionsFromProps]);

    return (
        <Widget>
            <DiscussionsWidget
                {...props}
                discussions={discussions}
                apiParams={{ ...apiParams, discussionID: discussions[0].discussionID }}
                containerOptions={{
                    ...containerOptions,
                    maxColumnCount:
                        containerOptions?.displayType === WidgetContainerDisplayType.CAROUSEL
                            ? 2
                            : containerOptions?.maxColumnCount,
                }}
                noCheckboxes
                disableButtonsInItems
            />
        </Widget>
    );
}
