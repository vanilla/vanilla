/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import SuggestedContentWidget, { ISuggestedContentWidgetProps } from "./SuggestedContentWidget";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";

interface IProps extends Omit<ISuggestedContentWidgetProps, "categories" | "discussions"> {}

export function SuggestedContentWidgetPreview(props: IProps) {
    return (
        <SuggestedContentWidget
            {...props}
            discussions={LayoutEditorPreviewData.discussions(props.suggestedContent?.limit)}
            categories={LayoutEditorPreviewData.categories(props.suggestedFollows.limit)}
            preview
        />
    );
}
