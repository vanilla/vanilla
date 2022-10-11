/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import BannerContentWidget from "@library/banner/BannerContentWidget";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps extends React.ComponentProps<typeof BannerContentWidget> {}

export function BannerContentWidgetPreview(_props: IProps) {
    const props: IProps = {
        ..._props,
        title: _props.title || t("Dynamic Title"),
        description: _props.description || t("A Dynamic Description will appear here."),
        showTitle: _props.showTitle ?? false,
        showDescription: _props.showDescription ?? false,
    };
    return <BannerContentWidget {...props} />;
}
