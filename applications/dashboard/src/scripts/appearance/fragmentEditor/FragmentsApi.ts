/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IUserFragment } from "@library/@types/api/users";
import apiv2, { type IUploadedFile } from "@library/apiv2";
import type { JsonSchema } from "@library/json-schema-forms";
import SimplePagerModel, { type IWithPaging } from "@library/navigation/SimplePagerModel";
import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";

export namespace FragmentsApi {
    export type Sort = "-dateRevisionInserted" | "dateRevisionInserted";

    export type AppliedStatus = "all" | "applied" | "not-applied";

    export type IndexParams = {
        sort: Sort;
        status?: Fragment["status"] | "latest";
        fragmentType?: string;
        appliedStatus?: AppliedStatus;
    };

    export type FragmentView = {
        recordType: "theme" | "layout";
        recordID: string;
        recordName: string;
        recordUrl: string;
    };

    export type Fragment = {
        fragmentUUID: string;
        name: string;
        fragmentType: string;
        fragmentRevisionUUID: string;
        revisionInsertUserID: number;
        revisionInsertUser: IUserFragment;
        dateRevisionInserted: string;
        commitMessage: string;
        commitDescription: string | null;
        status: "draft" | "active" | "past-revision";
        isLatest: boolean;
        url: string;
        isApplied: boolean;
        fragmentViews: FragmentView[];
    };

    export type Detail = Fragment & {
        js: string;
        jsRaw: string;
        css: string;
        previewData: IFragmentPreviewData[];
        isActiveRevision: boolean;
        files: IUploadedFile[];
        customSchema?: JsonSchema;
    };

    export interface CommitData {
        commitMessage: string;
        commitDescription: string;
    }

    export type CommitParams = CommitData & {
        fragmentRevisionUUID: string;
    };

    export type RevisionsParams = {
        page: number;
        limit: number;
    };

    export type GetParams = {
        fragmentRevisionUUID?: string;
        status?: "latest" | "active";
        layoutStatus?: "latest" | "applied" | "not-applied";
        fragmentType?: string;
    };

    export type PostParams = Pick<Detail, "js" | "jsRaw" | "css" | "name" | "fragmentType">;
    export type PatchParams = Omit<PostParams, "fragmentType">;
}

export const FragmentsApi = {
    async index(params: FragmentsApi.IndexParams): Promise<FragmentsApi.Fragment[]> {
        const response = await apiv2.get("/fragments", { params: { ...params, expand: ["users"] } });
        return response.data;
    },

    async get(fragmentUUID: string, params: FragmentsApi.GetParams): Promise<FragmentsApi.Detail> {
        const response = await apiv2.get(`/fragments/${fragmentUUID}`, {
            params: {
                ...params,
                expand: "users",
            },
        });
        return response.data;
    },

    async getRevisions(
        fragmentUUID: string,
        params: FragmentsApi.RevisionsParams,
    ): Promise<IWithPaging<FragmentsApi.Fragment[]>> {
        const response = await apiv2.get(`/fragments/${fragmentUUID}/revisions`, {
            params: { ...params, expand: "users" },
        });

        const paging = SimplePagerModel.parseHeaders(response.headers);
        return {
            data: response.data,
            paging,
        };
    },

    async commitRevision(fragmentUUID: string, params: FragmentsApi.CommitParams): Promise<void> {
        return await apiv2.post(`/fragments/${fragmentUUID}/commit-revision`, params);
    },

    async post(params: FragmentsApi.PostParams): Promise<FragmentsApi.Detail> {
        const response = await apiv2.post("/fragments", params);
        return response.data;
    },

    async patch(fragmentUUID: string, params: FragmentsApi.PatchParams): Promise<FragmentsApi.Detail> {
        const response = await apiv2.patch(`/fragments/${fragmentUUID}`, params);
        return response.data;
    },

    async delete(fragmentUUID: string, fragmentRevisionUUID?: string): Promise<void> {
        if (fragmentRevisionUUID) {
            await apiv2.delete(`/fragments/${fragmentUUID}?fragmentRevisionUUID=${fragmentRevisionUUID}`);
        } else {
            await apiv2.delete(`/fragments/${fragmentUUID}`);
        }
    },

    async getAcceptedDisclosure(): Promise<boolean> {
        const response = await apiv2.get<{ didAccept: boolean }>("/fragments/disclosure-state");
        const body = response.data;
        return body.didAccept ?? false;
    },

    async setAcceptedDisclosure(didAccept: boolean): Promise<void> {
        await apiv2.put("/fragments/disclosure-state", { didAccept });
    },
};
