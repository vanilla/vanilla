/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { IFeaturedImage } from "@library/@types/api/core";
import {
    IHomeWidgetContainerOptions,
    WidgetContainerDisplayType,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { featuredCollectionsVariables, ICollection } from "@library/featuredCollections/Collections.variables";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import omit from "lodash/omit";
import { List } from "@library/lists/List";
import { FeaturedCollectionRecord } from "@library/featuredCollections/FeaturedCollectionRecord";
import QuickLinks from "@library/navigation/QuickLinks";
import { INavigationVariableItem } from "@library/headers/navigationVariables";

export interface IFeaturedCollectionsOptions extends IHomeWidgetContainerOptions {
    featuredImage?: IFeaturedImage;
}

export interface IFeaturedCollectionsProps {
    title?: string;
    subtitle?: string;
    description?: string;
    options?: IFeaturedCollectionsOptions;
    collection?: ICollection;
}

export function FeaturedCollections(_props: IFeaturedCollectionsProps) {
    const { options, collection, ...props } = _props;
    const variables = featuredCollectionsVariables(options);

    const asTile = useMemo<boolean>(() => {
        return [WidgetContainerDisplayType.GRID, WidgetContainerDisplayType.CAROUSEL].includes(
            variables.options.displayType ?? WidgetContainerDisplayType.LIST,
        );
    }, [variables]);

    const containerOptions = {
        ...omit(options, "featuredImage"),
        displayType: options?.displayType ?? WidgetContainerDisplayType.LIST,
    };

    const records =
        collection?.records &&
        collection.records.map((record) => (
            <FeaturedCollectionRecord
                key={record.recordID}
                {...record}
                asTile={asTile}
                featuredImage={options?.featuredImage}
            />
        ));

    if (containerOptions.displayType === WidgetContainerDisplayType.LINK && collection?.records) {
        const recordLinks: INavigationVariableItem[] = collection.records.map((record) => ({
            id: record.recordID.toString(),
            name: record.record?.name ?? "",
            url: record.record?.url ?? "#",
        }));

        return <QuickLinks {...props} links={recordLinks} containerOptions={containerOptions} />;
    }

    return (
        <HomeWidgetContainer {...props} options={containerOptions}>
            {asTile ? records : <List>{records}</List>}
        </HomeWidgetContainer>
    );
}

export default FeaturedCollections;
