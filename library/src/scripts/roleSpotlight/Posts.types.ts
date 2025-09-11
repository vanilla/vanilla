/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";

export type Post = (IComment | IDiscussion) & {
    excerpt?: string;
};

export type GetPostsRequestBody = {
    roleIDs?: string | string[];
    page?: number;
    limit?: number;
} & (
    | {
          includeComments?: false;
          sort?: "-dateLastComment" | "-dateInserted" | "-score" | "dateInserted";
      }
    | {
          includeComments?: true;
          sort?: "-commentDate" | "-score";
      }
);
