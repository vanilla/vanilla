/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { IDraft, DraftStatus, DraftsSortValue, ILegacyDraft } from "@vanilla/addon-vanilla/drafts/types";
import { RecordID } from "@vanilla/utils";
import SimplePagerModel, { IWithPaging } from "@library/navigation/SimplePagerModel";

export const DraftsApi = {
    index: async (apiParams: DraftsApi.GetParams): Promise<IWithPaging<IDraft[]>> => {
        const response = await apiv2.get("/drafts", {
            params: apiParams,
        });
        return { data: response.data, paging: SimplePagerModel.parseHeaders(response.headers) };
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
    schedule: async (apiParams: { draftID: RecordID; dateScheduled: string }): Promise<void> => {
        await apiv2.patch(`/drafts/schedule/${apiParams.draftID}`, { dateScheduled: apiParams.dateScheduled });
    },
    cancelShedule: async (apiParams: { draftID: RecordID }): Promise<void> => {
        await apiv2.patch(`/drafts/cancel-schedule/${apiParams.draftID}`);
    },
};

export namespace DraftsApi {
    export interface GetParams {
        draftID?: RecordID;
        limit?: number;
        page?: number;
        draftStatus?: DraftStatus;
        expand?: boolean;
        sort?: DraftsSortValue;
        dateUpdated?: string;
        dateScheduled?: string;
        recordType?: IDraft["recordType"];
    }
    export interface PostParams {
        attributes: IDraft["attributes"] | ILegacyDraft["attributes"];
        recordType: IDraft["recordType"];
        parentRecordType?: string;
        parentRecordID?: RecordID;
        dateScheduled?: IDraft["dateScheduled"]; // on this date, the draft will be published as post
        draftStatus?: DraftStatus;

        recordID?: RecordID;
    }

    export interface PatchParams extends PostParams {
        draftID: RecordID;
    }

    export interface PatchScheduleParams {
        draftID: RecordID;
        dateScheduled: string;
    }
}
