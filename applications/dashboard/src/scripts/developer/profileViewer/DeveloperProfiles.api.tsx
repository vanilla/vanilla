/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDeveloperProfile, IDeveloperProfileDetails } from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import apiv2 from "@library/apiv2";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";

export namespace DeveloperProfilesApi {
    export type IndexSort = "dateRecorded" | "-dateRecorded" | "requestElapsedMs" | "-requestElapsedMs";
    export type IndexQuery = {
        sort: IndexSort;
        page: number;
        limit?: number;
        isTracked?: boolean;
        name?: string;
    };
    export type IndexResponse = {
        profiles: IDeveloperProfile[];
        pagination: ILinkPages;
    };
    export type PatchParams = {
        developerProfileID: number;
        isTracked?: boolean;
        name?: string;
    };

    export async function index(
        query: DeveloperProfilesApi.IndexQuery,
    ): Promise<{ pagination: ILinkPages; profiles: IDeveloperProfile[] }> {
        const response = await apiv2.get<IDeveloperProfile[]>(`/developer-profiles`, {
            params: {
                limit: 30,
                ...query,
            },
        });
        const pagination = SimplePagerModel.parseHeaders(response.headers);
        return {
            profiles: response.data,
            pagination,
        } as IndexResponse;
    }

    export async function details(profileID: number): Promise<IDeveloperProfileDetails> {
        const response = await apiv2.get<IDeveloperProfileDetails>(`/developer-profiles/${profileID}`);
        return response.data;
    }

    export async function patch(params: PatchParams): Promise<IDeveloperProfileDetails> {
        const { developerProfileID, ...body } = params;
        const response = await apiv2.patch<IDeveloperProfileDetails>(`/developer-profiles/${developerProfileID}`, body);
        return response.data;
    }
}
