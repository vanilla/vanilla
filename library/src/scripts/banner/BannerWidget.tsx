/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import Banner from "@library/banner/Banner";
import { BannerAlignment, SearchPlacement } from "@library/banner/Banner.variables";
import { IBackground } from "@library/styles/cssUtilsTypes";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ImageSourceSet } from "@library/utility/appUtils";

interface IProps {
    // Data
    title: string;
    description?: string;
    background?: Partial<IBackground> & { useOverlay?: boolean; imageUrlSrcSet?: ImageSourceSet };

    // Options
    showDescription?: boolean;
    showSearch?: boolean;
    showTitle?: boolean;
    searchPlacement?: SearchPlacement;

    textColor?: string;
    alignment?: BannerAlignment;
    isContentBanner?: boolean;
}

export default function BannerWidget(props: IProps) {
    return (
        <Banner
            isContentBanner={props.isContentBanner}
            title={props.title}
            description={props.description}
            backgroundImage={props.background?.image}
            backgroundUrlSrcSet={props.background?.imageUrlSrcSet}
            options={{
                enabled: true,
                hideTitle: props.showTitle !== undefined ? !props.showTitle : undefined, //will take variables value if undefined
                hideDescription: !props.showDescription,
                hideSearch: !props.showSearch,
                searchPlacement: props.searchPlacement,
                alignment: props.alignment,
                bgColor: props.background?.color ? ColorsUtils.ensureColorHelper(props.background?.color) : undefined,
                fgColor: props.textColor ? ColorsUtils.ensureColorHelper(props.textColor) : undefined,
                useOverlay: props.background?.useOverlay,
            }}
        />
    );
}
