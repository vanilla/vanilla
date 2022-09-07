/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentProps } from "react";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";
import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";
import QuickLinks from "@library/navigation/QuickLinks";
import { t } from "@vanilla/i18n";

export function RSSWidget(props: ComponentProps<typeof HomeWidget>) {
    const links = props.itemData.map((item, index) => {
        return {
            id: `${index}`,
            name: (item.name || item.description) ?? t("Unknown"),
            url: (item.to as string) ?? "#invalid-url",
        };
    });
    if (props.containerOptions?.displayType === WidgetContainerDisplayType.LINK) {
        return <QuickLinks title={props.title} links={links} containerOptions={props.containerOptions} />;
    } else {
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
}
export default RSSWidget;
