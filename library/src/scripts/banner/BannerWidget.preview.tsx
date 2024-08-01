/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import BannerWidget from "@library/banner/BannerWidget";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps extends React.ComponentProps<typeof BannerWidget> {
    titleType: string;
    descriptionType: string;
}

export function BannerWidgetPreview(_props: IProps) {
    const title = typeof _props.title === "string" && _props.title.length > 0 ? _props.title : t("Contextual Title");
    const description =
        typeof _props.description === "string" && _props.description.length > 0
            ? _props.description
            : t("Contextual Description");

    const showTitle = _props.titleType !== "none";
    const showDescription = _props.descriptionType !== "none";

    const props = {
        ..._props,
        title,
        description,
        showTitle,
        showDescription,
        background: {
            ..._props.background,
            image: _props.background?.imageSource === "custom" ? _props.background?.image : "",
        },
    };

    return <BannerWidget {...props} />;
}
