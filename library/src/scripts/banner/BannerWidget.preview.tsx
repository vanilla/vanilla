/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import BannerWidget from "@library/banner/BannerWidget";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps extends React.ComponentProps<typeof BannerWidget> {}

export function BannerWidgetPreview(_props: IProps) {
    const props = {
        ..._props,
        title: _props.title || t("Dynamic Title"),
        description: _props.description || t("A Dynamic Description will appear here."),
    };
    return <BannerWidget {...props} />;
}
