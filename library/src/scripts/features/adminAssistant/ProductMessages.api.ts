/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IUser, IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import type { VanillaSanitizedHtml } from "@vanilla/dom-utils";
import { t } from "@vanilla/i18n";
import { labelize } from "@vanilla/utils";

export namespace ProductMessagesApi {
    export interface Event {}

    export type MessageType = "announcement" | "personalMessage";

    export type AnnouncementType = "None" | "Inbox" | "Direct";

    export interface Message {
        productMessageID: string;
        productMessageType: MessageType;
        announcementType: AnnouncementType;
        foreignUrl?: string;
        name: string;
        body: VanillaSanitizedHtml;
        isDismissed: boolean;
        dateDismissed?: string;
        foreignInsertUser: IUserFragment;
        dateInserted: string;
        ctaLabel?: string;
        ctaUrl?: string;
        countViewers: number;
    }

    export type EditBody = {
        name: string;
        body: any;
        announcementType: AnnouncementType;
        format: string;
        foreignInsertUserID: number;
        ctaLabel?: string;
        ctaUrl?: string;
    };
}

export const ProductMessagesApi = {
    labelForType(type: ProductMessagesApi.MessageType): string {
        switch (type) {
            case "announcement":
                return t("Announcement");
            case "personalMessage":
                return t("Personal Message");
            default:
                return labelize(type);
        }
    },

    async listMessages() {
        const response = await apiv2.get<ProductMessagesApi.Message[]>("/product-messages");
        return response.data;
    },
    useListMessagesQuery() {
        return useQuery({
            queryKey: ["product-messages"],
            async queryFn() {
                const response = await ProductMessagesApi.listMessages();
                return response;
            },
        });
    },

    async dismiss(productMessageID: string) {
        await apiv2.post(`/product-messages/${productMessageID}/dismiss`, {});
    },

    useDismissMutation(productMessageID: string) {
        const queryClient = useQueryClient();
        return useMutation({
            async mutationFn() {
                await ProductMessagesApi.dismiss(productMessageID);
                await queryClient.invalidateQueries(["product-messages"]);
            },
        });
    },

    async dismissAll() {
        await apiv2.post(`/product-messages/dismiss-all`, {});
    },
    useDismissAllMutation() {
        const queryClient = useQueryClient();
        return useMutation({
            async mutationFn() {
                await ProductMessagesApi.dismissAll();
                await queryClient.invalidateQueries(["product-messages"]);
            },
        });
    },

    async getEdit(productMessageID: string) {
        const response = await apiv2.get<ProductMessagesApi.Message>(`/product-messages/${productMessageID}/edit`);
        return response.data;
    },

    useGetEditQuery(productMessageID: string) {
        return useQuery({
            queryKey: ["product-messages", productMessageID, "edit"],
            async queryFn() {
                const response = await ProductMessagesApi.getEdit(productMessageID);
                return response;
            },
        });
    },

    async save(body: ProductMessagesApi.EditBody & { productMessageID?: string }) {
        const { productMessageID, ...rest } = body;
        if (productMessageID) {
            const response = await apiv2.patch<ProductMessagesApi.Message>(
                `/product-messages/${body.productMessageID}`,
                rest,
            );
            return response.data;
        } else {
            const response = await apiv2.post<ProductMessagesApi.Message>("/product-messages", rest);
            return response.data;
        }
    },

    useSaveMutation(productMessageID?: string, options?: { onSuccess?: () => void }) {
        const queryClient = useQueryClient();
        return useMutation({
            async mutationFn(body: ProductMessagesApi.EditBody) {
                const response = await ProductMessagesApi.save({ ...body, productMessageID });
                await queryClient.invalidateQueries(["product-messages"]);
                options?.onSuccess?.();
                return response;
            },
        });
    },

    async delete(productMessageID: string) {
        await apiv2.delete(`/product-messages/${productMessageID}`);
    },
    useDeleteMutation(productMessageID: string) {
        const queryClient = useQueryClient();
        return useMutation({
            async mutationFn() {
                await ProductMessagesApi.delete(productMessageID);
                await queryClient.invalidateQueries(["product-messages"]);
            },
        });
    },

    async getViewers(productMessageID: string) {
        const response = await apiv2.get<IUser[]>(`/product-messages/${productMessageID}/viewers`);
        return response.data;
    },

    useGetViewersQuery(productMessageID: string) {
        return useQuery({
            queryKey: ["product-messages", productMessageID, "viewers"],
            async queryFn() {
                const response = await ProductMessagesApi.getViewers(productMessageID);
                return response;
            },
        });
    },
};
