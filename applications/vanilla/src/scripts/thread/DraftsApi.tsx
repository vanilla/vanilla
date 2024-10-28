/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDraft } from "@dashboard/@types/api/draft";
import apiv2 from "@library/apiv2";
import { RecordID } from "@vanilla/utils";

export const DraftsApi = {
    post: async (apiParams: DraftsApi.PostParams): Promise<IDraft> => {
        const result = await apiv2.post("/drafts", apiParams);
        return result.data;
    },
    patch: async (apiParams: DraftsApi.PatchParams): Promise<IDraft[]> => {
        const { draftID, ...rest } = apiParams;
        const result = await apiv2.patch(`/drafts/${draftID}`, rest);
        return result.data;
    },
};

export namespace DraftsApi {
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
