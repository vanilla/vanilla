/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { FeaturedCollectionsWidget } from "@library/featuredCollections/FeaturedCollectionsWidget";
import { CollectionRecordTypes } from "@library/featuredCollections/Collections.variables";

interface IProps extends Omit<React.ComponentProps<typeof FeaturedCollectionsWidget>, "collection"> {}

export function FeaturedCollectionsWidgetPreview(props: IProps) {
    const { containerOptions } = props;

    return (
        <LayoutWidget>
            <FeaturedCollectionsWidget
                {...props}
                containerOptions={{
                    ...containerOptions,
                    maxColumnCount:
                        containerOptions?.displayType === WidgetContainerDisplayType.CAROUSEL
                            ? 2
                            : containerOptions?.maxColumnCount,
                }}
                collection={{
                    collectionID: 1,
                    name: "Preview Collection",
                    dateInserted: "2020-10-06T15:30:44+00:00",
                    dateUpdated: "2020-10-06T15:30:44+00:00",
                    records: LayoutEditorPreviewData.discussions(10, true).map((record) => ({
                        recordID: record.discussionID,
                        recordType: CollectionRecordTypes.DISCUSSION,
                        record: {
                            name: record.name,
                            excerpt: record.excerpt,
                            url: record.url,
                            image: record.image,
                        },
                    })),
                }}
            />
        </LayoutWidget>
    );
}

export default FeaturedCollectionsWidgetPreview;
