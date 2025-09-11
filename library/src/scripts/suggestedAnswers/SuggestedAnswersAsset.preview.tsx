/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import SuggestedAnswersAsset from "@library/suggestedAnswers/SuggestedAnswersAsset";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const discussion = LayoutEditorPreviewData.discussion();

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

export function SuggestedAnswersAssetPreview() {
    return (
        <LayoutWidget>
            <QueryClientProvider client={queryClient}>
                <SuggestedAnswersAsset discussion={discussion} isPreview={true} />
            </QueryClientProvider>
        </LayoutWidget>
    );
}
