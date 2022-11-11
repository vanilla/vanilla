/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ICollectionResource } from "@library/featuredCollections/Collections.variables";
import { IFeaturedImage } from "@library/@types/api/core";
import { ListItem } from "@library/lists/ListItem";
import { homeWidgetItemClasses } from "@library/homeWidget/HomeWidgetItem.styles";
import { featuredCollectionsClasses } from "@library/featuredCollections/FeaturedCollections.style";
import { cx } from "@emotion/css";

interface IProps extends ICollectionResource {
    asTile?: boolean;
    featuredImage?: IFeaturedImage;
}

export function FeaturedCollectionRecord(props: IProps) {
    const { record, asTile, featuredImage } = props;
    const classes = featuredCollectionsClasses();

    return (
        <ListItem
            name={record?.name}
            description={record?.excerpt}
            image={record?.image}
            featuredImage={featuredImage}
            asTile={asTile}
            url={record?.url}
            className={cx(homeWidgetItemClasses().root, !asTile && classes.listWrapper)}
        />
    );
}

export default FeaturedCollectionRecord;
