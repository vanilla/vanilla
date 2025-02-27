/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IComment } from "@dashboard/@types/api/comment";
import React from "react";

export type CommentActionsComponentType = React.ComponentType<{
    comment: IComment;
    onMutateSuccess?: () => Promise<void>;
}>;
