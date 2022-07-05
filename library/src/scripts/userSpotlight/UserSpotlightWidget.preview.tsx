/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { UserSpotlightWidget } from "@library/userSpotlight/UserSpotlightWidget";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { BorderType } from "@library/styles/styleHelpersBorders";

interface IProps extends Omit<React.ComponentProps<typeof UserSpotlightWidget>, "userInfo"> {}

export function UserSpotlightWidgetPreview(props: IProps) {
    return (
        <UserSpotlightWidget
            title={props.title ?? "Customer Spotlight"}
            subtitle={props.subtitle ?? ""}
            description={
                props.description ??
                "“Use this space to add a Customer Spotlight by telling the customer's story using their unique language, share what problems they experienced, and how they conquered it by using your product(s).”"
            }
            userInfo={LayoutEditorPreviewData.user()}
            containerOptions={props.containerOptions}
        />
    );
}
