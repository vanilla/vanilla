/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";

export const ManageIconsApi = {
    async getActive(): Promise<ManageIconsApi.IManagedIcon[]> {
        const response = await apiv2.get("/icons/active", {
            params: {
                expand: "users",
            },
        });
        return response.data;
    },

    async getSystem(): Promise<ManageIconsApi.IManagedIcon[]> {
        const response = await apiv2.get("/icons/system", {
            params: {
                expand: "users",
            },
        });
        return response.data;
    },

    async getRevisions(iconName: string): Promise<ManageIconsApi.IManagedIcon[]> {
        const response = await apiv2.get("/icons/by-name", {
            params: {
                iconName,
                expand: "users",
            },
        });
        return response.data;
    },

    async uploadIcons(
        iconOverrides: Array<{ iconName: string; svgRaw: string }>,
    ): Promise<ManageIconsApi.IManagedIcon> {
        const response = await apiv2.post("/icons/override", {
            iconOverrides,
        });
        return response.data;
    },

    async uploadIcon(iconName: string, svgRaw: string): Promise<ManageIconsApi.IManagedIcon> {
        return this.uploadIcons([{ iconName, svgRaw }]);
    },

    async restoreIcon(iconName: string, iconUUID: string): Promise<ManageIconsApi.IManagedIcon> {
        const response = await apiv2.post("/icons/restore", {
            restorations: [
                {
                    iconName,
                    iconUUID,
                },
            ],
        });
        return response.data;
    },

    async deleteIcon(iconUUID: string): Promise<void> {
        await apiv2.delete(`/icons/${iconUUID}`);
    },
};

export namespace ManageIconsApi {
    export type IManagedIcon = {
        iconUUID: string;
        iconName: string;
        svgRaw: string;
        // This might be filled on a temporary basis, but is not persisted to the database.
        // Don't ever render this.
        unsafeSvgRaw?: string;
        svgContents: string;
        svgAttributes: Record<string, any>;
        insertUserID: number;
        insertUser: IUserFragment;
        dateInserted: string;
        isActive: boolean;
        isCustom: boolean;
        frontendState?: "active" | "inactive" | "pending-active" | "pending-inactive";
    };
}
