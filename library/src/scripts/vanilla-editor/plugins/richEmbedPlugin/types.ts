/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IBaseEmbedData } from "@library/embeddedContent/embedService.register";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { TElement } from "@udecode/plate-common";

export const ELEMENT_RICH_EMBED_CARD = "rich_embed_card";
export const ELEMENT_RICH_EMBED_INLINE = "rich_embed_inline";
export const ELEMENT_LEGACY_EMOJI = "legacy_emoji_image";

export interface IRichLinkEmbedDataSource {
    dataSourceType: "url";
    url: string;
}

export interface IFileEmbedDataSource {
    dataSourceType: "image" | "file";
    uploadFile: File;
}

export interface IIframeEmbedDataSource {
    dataSourceType: "iframe";
    url: string;
    frameAttributes: {
        height: HTMLIFrameElement["style"]["height"];
        width: HTMLIFrameElement["style"]["width"];
    };
}

export type EmbedDataSources = IRichLinkEmbedDataSource | IFileEmbedDataSource | IIframeEmbedDataSource;

export type IRichEmbedElement = TElement & {
    type: typeof ELEMENT_RICH_EMBED_CARD | typeof ELEMENT_RICH_EMBED_INLINE;
    embedData?: IBaseEmbedData;
    url?: string;
    error?: IError;
} & EmbedDataSources;

export enum RichLinkAppearance {
    LINK = "link",
    INLINE = "inline",
    CARD = "card",
}

export type ILegacyEmoji = TElement & {
    type: typeof ELEMENT_LEGACY_EMOJI;
    attributes: {
        src: string;
        alt?: string;
        title?: string;
        className?: string;
    };
};
