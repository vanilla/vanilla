/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { withTable } from "@library/vanilla-editor/plugins/tablePlugin/withTable";
import { ELEMENT_TABLE, ELEMENT_TR, ELEMENT_TH, ELEMENT_TD, createPluginFactory } from "@udecode/plate-headless";

export const ELEMENT_CAPTION = "caption";
export const ELEMENT_TBODY = "tbody";
export const ELEMENT_THEAD = "thead";
export const ELEMENT_TFOOT = "tfoot";

export const createTablePlugin = createPluginFactory<{}>({
    key: ELEMENT_TABLE,
    isElement: true,
    deserializeHtml: {
        attributeNames: ["role"],
        rules: [{ validNodeName: "TABLE" }],
    },
    props: ({ element }) => ({
        nodeProps: {
            role: (element?.attributes as any)?.role,
        },
    }),
    withOverrides: withTable,
    plugins: [
        {
            key: ELEMENT_CAPTION,
            isElement: true,
            deserializeHtml: { rules: [{ validNodeName: "CAPTION" }] },
        },
        {
            key: ELEMENT_TBODY,
            isElement: true,
            deserializeHtml: { rules: [{ validNodeName: "TBODY" }] },
        },
        {
            key: ELEMENT_THEAD,
            isElement: true,
            deserializeHtml: { rules: [{ validNodeName: "THEAD" }] },
        },
        {
            key: ELEMENT_TFOOT,
            isElement: true,
            deserializeHtml: { rules: [{ validNodeName: "TFOOT" }] },
        },
        {
            key: ELEMENT_TR,
            isElement: true,
            deserializeHtml: { rules: [{ validNodeName: "TR" }] },
        },
        {
            key: ELEMENT_TD,
            isElement: true,
            deserializeHtml: {
                attributeNames: ["rowspan", "colspan", "headers"],
                rules: [{ validNodeName: "TD" }],
            },
            props: ({ element }) => ({
                nodeProps: {
                    rowSpan: (element?.attributes as any)?.rowspan,
                    colSpan: (element?.attributes as any)?.colspan,
                    headers: (element?.attributes as any)?.headers,
                },
            }),
        },
        {
            key: ELEMENT_TH,
            isElement: true,
            deserializeHtml: {
                attributeNames: ["rowspan", "colspan", "scope", "id"],
                rules: [{ validNodeName: "TH" }],
            },
            props: ({ element }) => ({
                nodeProps: {
                    rowSpan: (element?.attributes as any)?.rowspan,
                    colSpan: (element?.attributes as any)?.colspan,
                    scope: (element?.attributes as any)?.scope,
                    id: (element?.attributes as any)?.id,
                },
            }),
        },
    ],
});
