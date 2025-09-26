import React from "react";
import { IEmbedContext } from "./IEmbedContext";

export interface IBaseEmbedData {
    embedType: string;
    url: string;
    name?: string;
    faviconUrl?: string;
    embedStyle?: "rich_embed_inline" | "rich_embed_card";
    displaySize?: "large" | "medium" | "small" | "inline";
}

export interface IBaseEmbedProps extends IEmbedContext, IBaseEmbedData {
    [key: string]: any;
}
export type EmbedComponentType = React.ComponentType<IBaseEmbedProps> & {
    async?: boolean;
};
export const registeredEmbeds = new Map<string, EmbedComponentType>();

export function registerEmbed(embedType: string, EmbedComponent: EmbedComponentType) {
    registeredEmbeds.set(embedType, EmbedComponent);
}

export function getEmbedForType(embedType: string): EmbedComponentType | null {
    return registeredEmbeds.get(embedType) || null;
}
