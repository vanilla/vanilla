/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import BannerContentWidget from "@library/banner/BannerContentWidget";
import { BannerWidgetPreview } from "@library/banner/BannerWidget.preview";
import React from "react";

interface IProps extends React.ComponentProps<typeof BannerContentWidget> {}

export function BannerContentWidgetPreview(_props: IProps) {
    return <BannerWidgetPreview {...(_props as any)} isContentBanner={true} />;
}
