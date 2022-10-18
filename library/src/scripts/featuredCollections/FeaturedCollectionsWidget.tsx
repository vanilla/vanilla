/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import {
    FeaturedCollections,
    IFeaturedCollectionsProps,
    IFeaturedCollectionsOptions,
} from "@library/featuredCollections/FeaturedCollections";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import omit from "lodash/omit";
import { useConfigsByKeys } from "@library/config/configHooks";
import { LoadStatus } from "@library/@types/api/core";
import { CONFIG_FEATURED_COLLECTIONS } from "@library/featuredCollections/FeaturedCollections.variables";

interface IProps extends IFeaturedCollectionsProps {
    containerOptions?: IHomeWidgetContainerOptions;
    displayOptions?: {
        featuredImage?: boolean;
        fallbackImage?: string;
    };
}

export function FeaturedCollectionsWidget(_props: IProps) {
    const { containerOptions, displayOptions, ...props } = _props;
    const config = useConfigsByKeys([CONFIG_FEATURED_COLLECTIONS]);

    const componentProps = useMemo<IFeaturedCollectionsProps>(() => {
        return omit(props, "apiParams");
    }, [props]);

    const options = useMemo<IFeaturedCollectionsOptions>(() => {
        return {
            ...containerOptions,
            ...(displayOptions?.featuredImage && {
                featuredImage: {
                    display: displayOptions.featuredImage,
                    fallbackImage: displayOptions.fallbackImage,
                },
            }),
        };
    }, [containerOptions, displayOptions]);

    if (config.status === LoadStatus.SUCCESS && config.data[CONFIG_FEATURED_COLLECTIONS]) {
        return <FeaturedCollections {...componentProps} options={options} />;
    }

    return null;
}

export default FeaturedCollectionsWidget;
