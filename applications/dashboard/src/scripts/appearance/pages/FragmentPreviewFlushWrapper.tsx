/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PropsWithChildren } from "react";

/**
 * Only use this in fragment previews to allow
 * widgets to render against the viewport edges
 */
export function FragmentPreviewFlushWrapper<T>(props: PropsWithChildren<T>) {
    return <div style={{ margin: -16 }}>{props.children}</div>;
}
