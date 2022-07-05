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

interface IProps {
    // Data
    title: string;
    description?: string;
    background?: Partial<IBackground> & { useOverlay?: boolean };

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
            options={{
                enabled: true,
                hideTitle: !(props.showTitle ?? true),
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
