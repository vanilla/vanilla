/**
 * Embed utility functions and types.
 * This file should have NO external dependencies other than javascript.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussionEmbed } from "@dashboard/@types/api/discussion";
import { ICommentEmbed } from "@dashboard/@types/api/comment";
import { IScrapeData } from "@dashboard/@types/api/media";
import ReactDOM from "react-dom";
import React from "react";

export const FOCUS_CLASS = "embed-focusableElement";

export type IQuoteEmbedData = IDiscussionEmbed | ICommentEmbed;
export type IEmbedData = IScrapeData | IFileUploadData;

export interface IFileUploadData {
    embedType: "file";
    url: string;
    attributes: {
        mediaID: number;
        url: string;
        name: string;
        type: string;
        size: number;
        dateInserted: string;
    };
}

export interface IEmbedElements {
    root: HTMLElement;
    content: HTMLElement;
}

export type EmbedRenderer = (elements: IEmbedElements, data: IEmbedData, inEditor: boolean) => Promise<void>;

export interface IEmbedProps<T = IScrapeData> {
    data: T;
    inEditor: boolean;
    onRenderComplete: () => void;
}

const embedRenderers: {
    [type: string]: EmbedRenderer;
} = {};
const embedComponents: {
    [type: string]: React.ComponentClass<IEmbedProps>;
} = {};

/**
 * Get all of the registered embed types.
 */
export function getEditorEmbedTypes() {
    return Object.keys(embedRenderers);
}

/**
 * Register an embed rendering function.
 */
export function registerEmbedRenderer(type: string, renderer: EmbedRenderer) {
    embedRenderers[type] = renderer;
}

/**
 * Register an embed rendering function.
 */
export function registerEmbedComponent(type: string, component: React.ComponentClass<IEmbedProps<any>>) {
    embedComponents[type] = component;
}

/**
 * Render an embed into a DOM node based on it's type.
 */
export function renderEmbed(elements: IEmbedElements, data: IEmbedData, inEditor = true): Promise<void> {
    return new Promise((resolve, reject) => {
        if (!data.embedType) {
            throw new Error("The embed type was not provided.");
        }

        if (data.embedType === "link") {
            elements.root.classList.add("embedText");
            elements.content.classList.add("embedText-content");
            elements.content.classList.add("embedLink-content");
        }

        if (data.embedType === "quote") {
            elements.root.classList.add("embedText");
            elements.content.classList.add("embedText-content");
            elements.content.classList.add("embedQuote-content");
        }

        const renderer = data.embedType && embedRenderers[data.embedType];
        const Component = data.embedType && embedComponents[data.embedType];

        if (renderer) {
            return renderer(elements, data, inEditor);
        } else if (Component) {
            ReactDOM.render(
                <Component data={data as IScrapeData} inEditor={inEditor} onRenderComplete={resolve} />,
                elements.content,
            );
        } else {
            throw new Error("Could not find a renderer for the embed type - " + data.embedType);
        }
    });
}
