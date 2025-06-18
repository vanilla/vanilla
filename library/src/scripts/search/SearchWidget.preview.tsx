/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutWidget } from "@library/layout/LayoutWidget";
import WidgetPreviewNoPointerEventsWrapper from "@library/layout/WidgetPreviewNoPointerEventsWrapper";
import SearchWidget from "@library/search/SearchWidget";
import { stableObjectHash } from "@vanilla/utils";

interface ISearchWidgetPreviewProps extends React.ComponentProps<typeof SearchWidget> {}

export function SearchWidgetPreview(props: ISearchWidgetPreviewProps) {
    return (
        <LayoutWidget>
            <WidgetPreviewNoPointerEventsWrapper>
                <SearchWidget key={stableObjectHash(props)} {...props} />
            </WidgetPreviewNoPointerEventsWrapper>
        </LayoutWidget>
    );
}
