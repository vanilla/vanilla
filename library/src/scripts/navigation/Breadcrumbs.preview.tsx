/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import React from "react";

interface IProps extends Omit<React.ComponentProps<typeof Breadcrumbs>, "events"> {}

export function BreadcrumbsWidgetPreview(props: IProps) {
    return <Breadcrumbs {...props}>{LayoutEditorPreviewData.getBreadCrumbs()}</Breadcrumbs>;
}
