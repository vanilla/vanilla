/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IEditableLayoutSpec, IEditableLayoutWidget } from "@dashboard/layout/layoutSettings/LayoutSettings.types";

import { IRole } from "@dashboard/roles/roleTypes";
import { ITitleBarParams } from "@library/headers/TitleBar.ParamContext";
import { RecordID } from "@vanilla/utils";
import apiv2 from "@library/apiv2";

export const CustomPagesAPI = {
    async get(status?: CustomPagesAPI.Status): Promise<CustomPagesAPI.Page[]> {
        const response = await apiv2.get("/custom-pages", {
            params: {
                ...(status && { status }),
            },
        });
        return response.data;
    },

    async post(params: CustomPagesAPI.CreateParams): Promise<CustomPagesAPI.Page> {
        const response = await apiv2.post(`/custom-pages`, {
            ...params,
        });
        return response.data;
    },

    async patch(
        customPageID: CustomPagesAPI.Page["customPageID"],
        params: Partial<CustomPagesAPI.Page>,
    ): Promise<CustomPagesAPI.Page[]> {
        const response = await apiv2.patch(`/custom-pages/${customPageID}`, {
            ...params,
        });
        return response.data;
    },

    async delete(customPageID: CustomPagesAPI.Page["customPageID"]): Promise<CustomPagesAPI.Page[]> {
        const response = await apiv2.delete(`/custom-pages/${customPageID}`);
        return response.data;
    },
};

export namespace CustomPagesAPI {
    export type Status = "published" | "unpublished";
    type CommonPageParams = {
        seoTitle: string;
        seoDescription: string;
        urlcode: string;
        status: string;
        siteSectionID: string;
        roleIDs: Array<IRole["roleID"]>;
        rankIDs: number[];
    };
    export type Page = CommonPageParams & {
        customPageID: number;
        layoutID: number;
        url: string;
    };
    export type CreateParams = CommonPageParams & {
        layoutData?: {
            name: string;
            layout: IEditableLayoutWidget[];
            titleBar: IEditableLayoutWidget | ITitleBarParams;
        };
        copyLayoutID?: RecordID;
    };
}
