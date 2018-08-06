/**
 * Embed utility functions and types.
 * This file should have NO external dependencies other than javascript.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import ReactDOM from "react-dom";
import { IDiscussionEmbed, ICommentEmbed, IScrapeData } from "@dashboard/@types/api";

export const FOCUS_CLASS = "embed-focusableElement";

export type IQuoteEmbedData = IDiscussionEmbed | ICommentEmbed;
export type IEmbedData = IScrapeData;

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
export function registerEmbedComponent(type: string, component: React.ComponentClass<IEmbedProps>) {
    embedComponents[type] = component;
}

/**
 * Render an embed into a DOM node based on it's type.
 */
export function renderEmbed(elements: IEmbedElements, data: IEmbedData, inEditor = true): Promise<void> {
    return new Promise((resolve, reject) => {
        if (!data.type) {
            throw new Error("The embed type was not provided.");
        }

        if (data.type === "quote" || data.type === "link") {
            elements.root.classList.add("embedText");
        }

        const renderer = data.type && embedRenderers[data.type];
        const Component = data.type && embedComponents[data.type];

        if (renderer) {
            return renderer(elements, data, inEditor);
        } else if (Component) {
            ReactDOM.render(
                <Component data={data as IScrapeData} inEditor={inEditor} onRenderComplete={resolve} />,
                elements.content,
            );
        } else {
            throw new Error("Could not find a renderer for the embed type - " + data.type);
        }
    });
}
