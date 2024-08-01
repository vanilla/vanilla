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
            title={props.title}
            subtitle={props.subtitle ?? ""}
            description={props.description}
            userInfo={LayoutEditorPreviewData.user()}
            containerOptions={props.containerOptions}
        />
    );
}
