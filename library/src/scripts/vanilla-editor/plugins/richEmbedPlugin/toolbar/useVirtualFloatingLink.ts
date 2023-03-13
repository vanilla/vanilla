/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useVirtualFloating, UseVirtualFloatingOptions } from "@udecode/plate-floating";
import { floatingLinkActions } from "@udecode/plate-headless";

export const useVirtualFloatingLink = ({
    editorId,
    ...floatingOptions
}: { editorId: string } & UseVirtualFloatingOptions) => {
    return useVirtualFloating({
        onOpenChange: (open) => floatingLinkActions.openEditorId(open ? editorId : null),
        ...floatingOptions,
    });
};
