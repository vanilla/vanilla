/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import BannerWidget from "@library/banner/BannerWidget";
import React from "react";
import { options } from "yargs";

interface IProps extends React.ComponentProps<typeof BannerWidget> {}

export default function BannerContentWidget(props: IProps) {
    return <BannerWidget {...props} isContentBanner />;
}
