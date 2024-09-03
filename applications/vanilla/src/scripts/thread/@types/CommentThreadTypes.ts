/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";

type CommonThreadItem = {
    /* ID of the direct parent comment */
    parentCommentID: IComment["commentID"] | null;
    /* Level of nesting */
    depth: number;
};

export type IThreadItem = CommonThreadItem & { children?: IThreadItem[] } & (
        | {
              type: "comment";
              /* ID of the comment */
              commentID: IComment["commentID"];
          }
        | {
              /* Represents a gap in the nested structure */
              type: "hole";
              /* A generated hole ID based on the parent comment and offset */
              holeID?: string;
              /* Position from which new comments should be fetched*/
              offset: number;
              /* The first 5 users who are in the missing comments */
              insertUsers: any[];
              /* Total number of comments in the hole */
              countAllComments: number;
              /* Total number of user who have made a commented in the hole */
              countAllInsertUsers: number;
              /* URL to get additional comments*/
              apiUrl: string;
              /* Dot delimited location of hole relative to root */
              path: string;
          }
    );

export interface IThreadResponse {
    threadStructure: IThreadItem[];
    commentsByID: Record<IComment["commentID"], IComment>;
}
