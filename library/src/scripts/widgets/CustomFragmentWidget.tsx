/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IHydratedLayoutFragmentImpl } from "@library/features/Layout/LayoutRenderer.types";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { createLazyFragmentComponent } from "@library/utility/FragmentImplContext";
import { useMemo } from "react";

interface IProps {
    fragmentImpl: IHydratedLayoutFragmentImpl & { fragmentType: string };
    [key: string]: any; // Custom props.
}

export function CustomFragmentWidget(props: IProps) {
    const { fragmentImpl, ...rest } = props;

    const Component = useMemo(() => {
        return createLazyFragmentComponent(fragmentImpl.fragmentType, fragmentImpl);
    }, [fragmentImpl]);

    return <Component {...rest} />;
}
