/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { RecordID } from "@vanilla/utils";

export const DraftsApi = {
    get: async (apiParams: DraftsApi.GetParams): Promise<IDraft | IDraft[]> => {
        const result = await apiv2.get("/drafts", {
            params: apiParams,
        });
        return result.data;
    },
    getEdit: async (apiParams: DraftsApi.GetParams): Promise<IDraft> => {
        const result = await apiv2.get(`/drafts/${apiParams.draftID}/edit`);
        return result.data;
    },
    post: async (apiParams: DraftsApi.PostParams): Promise<IDraft> => {
        const result = await apiv2.post("/drafts", apiParams);
        return result.data;
    },
    patch: async (apiParams: DraftsApi.PatchParams): Promise<IDraft> => {
        const { draftID, ...rest } = apiParams;
        const result = await apiv2.patch(`/drafts/${draftID}`, rest);
        return result.data;
    },
    delete: async (apiParams: { draftID: RecordID }): Promise<void> => {
        await apiv2.delete(`/drafts/${apiParams.draftID}`);
    },
};

export namespace DraftsApi {
    export interface GetParams {
        draftID: RecordID;
    }
    export interface PostParams {
        attributes: IDraft["attributes"];
        recordType: "discussion" | "comment";
        parentRecordType?: string;
        parentRecordID?: RecordID;
    }

    export interface PatchParams extends PostParams {
        draftID: RecordID;
    }
}
