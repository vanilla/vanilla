/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import SuggestedContentWidget, { ISuggestedContentWidgetProps } from "./SuggestedContentWidget";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { QueryClient } from "@tanstack/react-query";
import { QueryClientProvider } from "@tanstack/react-query";
import { Widget } from "@library/layout/Widget";
import WidgetPreviewNoPointerEventsWrapper from "@library/layout/WidgetPreviewNoPointerEventsWrapper";

interface IProps extends Omit<ISuggestedContentWidgetProps, "categories" | "discussions"> {}

const mockQueryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

export function SuggestedContentWidgetPreview(props: IProps) {
    return (
        <Widget>
            <QueryClientProvider client={mockQueryClient}>
                <WidgetPreviewNoPointerEventsWrapper>
                    <SuggestedContentWidget
                        {...props}
                        discussions={LayoutEditorPreviewData.discussions(props.suggestedContent?.limit)}
                        categories={LayoutEditorPreviewData.categories(props.suggestedFollows.limit)}
                        preview
                    />
                </WidgetPreviewNoPointerEventsWrapper>
            </QueryClientProvider>
        </Widget>
    );
}
