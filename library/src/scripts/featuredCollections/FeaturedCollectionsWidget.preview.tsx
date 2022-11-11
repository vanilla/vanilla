/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import { FeaturedCollectionsWidget } from "@library/featuredCollections/FeaturedCollectionsWidget";
import { CollectionRecordTypes } from "@library/featuredCollections/Collections.variables";

interface IProps extends Omit<React.ComponentProps<typeof FeaturedCollectionsWidget>, "collection"> {}

export function FeaturedCollectionsWidgetPreview(props: IProps) {
    const { containerOptions } = props;

    return (
        <Widget>
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
        </Widget>
    );
}

export default FeaturedCollectionsWidgetPreview;
