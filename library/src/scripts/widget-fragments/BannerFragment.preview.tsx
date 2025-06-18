/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { FragmentPreviewFlushWrapper } from "@dashboard/appearance/pages/FragmentPreviewFlushWrapper";
import BannerFragmentInjectable from "@vanilla/injectables/BannerFragment";
import React from "react";

export default function BannerFragmentPreview(props: {
    previewData: BannerFragmentInjectable.Props;
    children?: React.ReactNode;
}) {
    return (
        <>
            <FragmentPreviewFlushWrapper>{props.children}</FragmentPreviewFlushWrapper>
        </>
    );
}
