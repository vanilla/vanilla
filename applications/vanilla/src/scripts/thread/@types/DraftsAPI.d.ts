/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

declare namespace DraftsApi {
    export interface PostParams {
        attributes: {
            format: string;
            body: string;
        };
        discussionID?: RecordID;
        parentRecordID?: RecordID;
        recordType: "discussion" | "comment";
    }

    export interface PatchParams extends PostParams {
        draftID: RecordID;
    }
}
