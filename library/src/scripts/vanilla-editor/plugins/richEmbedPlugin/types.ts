/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IBaseEmbedData } from "@library/embeddedContent/embedService";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { TElement } from "@udecode/plate-headless";

export const ELEMENT_RICH_EMBED_CARD = "rich_embed_card";
export const ELEMENT_RICH_EMBED_INLINE = "rich_embed_inline";

interface IRichLinkEmbedDataSource {
    dataSourceType: "url";
    url: string;
}

interface IFileEmbedDataSource {
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

type EmbedDataSources = IRichLinkEmbedDataSource | IFileEmbedDataSource | IIframeEmbedDataSource;

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
