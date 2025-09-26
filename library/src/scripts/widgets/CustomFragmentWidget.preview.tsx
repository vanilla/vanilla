/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IHydratedLayoutFragmentImpl } from "@library/features/Layout/LayoutRenderer.types";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { siteUrl } from "@library/utility/appUtils";
import { CustomFragmentWidget } from "@library/widgets/CustomFragmentWidget";
import { stableObjectHash } from "@vanilla/utils";
import { useMemo } from "react";

interface IProps {
    fragmentImpl: IHydratedLayoutFragmentImpl & { fragmentType: string };
    [key: string]: any; // Custom props.
}

export function CustomFragmentWidgetPreview(props: any) {
    const fragmentImpl = useMemo(() => {
        return {
            ...props.fragmentImpl,

            // In the preview we don't have the full definition, just a partial.
            jsUrl: props.fragmentImpl.jsUrl ?? siteUrl(`/api/v2/fragments/${props.fragmentImpl.fragmentUUID}/js`),
            cssUrl: props.fragmentImpl.cssUrl ?? siteUrl(`/api/v2/fragments/${props.fragmentImpl.fragmentUUID}/css`),
        };
    }, [stableObjectHash(props.fragmentImpl)]);

    return <CustomFragmentWidget {...props} fragmentImpl={fragmentImpl} />;
}
