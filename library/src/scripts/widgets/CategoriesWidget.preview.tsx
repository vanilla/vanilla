/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { CategoriesWidget } from "@library/widgets/CategoriesWidget";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { IHomeWidgetItemOptions } from "@library/homeWidget/WidgetItemOptions";
import { DeepPartial } from "redux";
import { getMeta } from "@library/utility/appUtils";
import { STORY_LEADERS } from "@library/storybook/storyData";
import random from "lodash-es/random";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import type { IDiscussion } from "@dashboard/@types/api/discussion";

interface IProps extends Omit<React.ComponentProps<typeof CategoriesWidget>, "itemData"> {
    itemOptions?: DeepPartial<IHomeWidgetItemOptions> & {
        fallbackIcon?: string;
        fallbackImage?: string;
    };
}

export function CategoriesWidgetPreview(props: IProps) {
    const itemData = LayoutEditorPreviewData.categories(6, {
        fallbackIcon: props.itemOptions?.fallbackIcon,
        fallbackImage: props.itemOptions?.fallbackImage,
    });

    itemData.forEach((item) => {
        const counts = [
            { count: 99000, countAll: 99000, labelCode: "comments" },
            { count: 99, countAll: 99, labelCode: "discussions" },
        ];
        const moreCounts = [
            { count: 99099, countAll: 99099, labelCode: "posts" },
            { count: 9, countAll: 9, labelCode: "followers" },
        ];

        item.counts =
            props.containerOptions?.displayType === WidgetContainerDisplayType.CAROUSEL
                ? counts
                : [...counts, ...moreCounts];

        item.lastPost = {
            url: "#",
            name: "Some discussion",
            dateInserted: "2020-10-06T15:30:44+00:00",
            insertUser: STORY_LEADERS[random(0, 5)].user,
            discussionID: 1,
            insertUserID: 1,
        };
    });

    return <CategoriesWidget {...props} itemData={itemData} isPreview />;
}
