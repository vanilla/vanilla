/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import TitleBar from "@library/headers/TitleBar";

export function TitleBarPreview(props: React.ComponentProps<typeof TitleBar>) {
    return <TitleBar {...props} container={null} />;
}
