/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { FragmentPreviewFlushWrapper } from "@dashboard/appearance/pages/FragmentPreviewFlushWrapper";
import SiteTotalsFragmentInjectable from "@vanilla/injectables/SiteTotalsFragment";
import React from "react";

export default function SiteTotalsFragmentPreview(props: {
    previewData: SiteTotalsFragmentInjectable.Props;
    children?: React.ReactNode;
}) {
    return (
        <>
            <FragmentPreviewFlushWrapper>{props.children}</FragmentPreviewFlushWrapper>
        </>
    );
}
