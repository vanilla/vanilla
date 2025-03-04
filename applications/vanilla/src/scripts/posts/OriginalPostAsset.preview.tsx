/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import OriginalPostAsset from "@vanilla/addon-vanilla/posts/OriginalPostAsset";
import React from "react";
import set from "lodash/set";

interface IProps extends Omit<React.ComponentProps<typeof OriginalPostAsset>, "comments" | "discussion"> {}

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});
export function OriginalPostAssetPreview(props: IProps) {
    const discussion = LayoutEditorPreviewData.discussion();

    const hasExternalData =
        LayoutEditorPreviewData.externallyRegisteredData?.externalData &&
        Object.values(LayoutEditorPreviewData.externallyRegisteredData.externalData).length > 0;

    if (hasExternalData) {
        const externalData = LayoutEditorPreviewData.externallyRegisteredData.externalData;
        Object.keys(externalData ?? {}).forEach((dataKey) => {
            if (externalData?.[dataKey] && discussion?.insertUser) {
                set(discussion, externalData?.[dataKey].path, externalData?.[dataKey].registererData);
            }
        });
    }

    return (
        <Widget>
            <QueryClientProvider client={queryClient}>
                <OriginalPostAsset
                    {...props}
                    category={discussion.category!}
                    discussion={discussion}
                    containerOptions={{
                        borderType: BorderType.SEPARATOR,
                        visualBackgroundType: "outer",
                        ...props.containerOptions,
                    }}
                    isPreview
                />
            </QueryClientProvider>
        </Widget>
    );
}
