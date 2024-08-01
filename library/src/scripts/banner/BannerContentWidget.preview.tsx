/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import BannerContentWidget from "@library/banner/BannerContentWidget";
import React from "react";

interface IProps extends React.ComponentProps<typeof BannerContentWidget> {}

export function BannerContentWidgetPreview(_props: IProps) {
    const props: IProps = {
        ..._props,
        title: _props.title,
        description: _props.description,
        showTitle: _props.showTitle,
        showDescription: _props.showDescription,
    };
    return <BannerContentWidget {...props} />;
}
