/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentProps } from "react";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";
import { HomeWidget } from "@library/homeWidget/HomeWidget";

export function RSSWidget(props: ComponentProps<typeof HomeWidget>) {
    return (
        <HomeWidget
            {...props}
            itemOptions={{
                ...props.itemOptions,
                contentType: props.itemOptions?.contentType
                    ? props.itemOptions?.contentType
                    : HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
            }}
        />
    );
}
export default RSSWidget;
