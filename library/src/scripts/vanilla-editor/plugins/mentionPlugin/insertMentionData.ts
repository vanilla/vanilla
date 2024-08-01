/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { deserializeHtml } from "@library/vanilla-editor/VanillaEditor.loadable";
import { ELEMENT_MENTION } from "@library/vanilla-editor/plugins/mentionPlugin/createMentionPlugin";
import { MyEditor, MyNode, MyValue } from "@library/vanilla-editor/typescript";
import {
    EElementOrText,
    ELEMENT_DEFAULT,
    focusEditor,
    getNextNode,
    getNodeDescendants,
    getParentNode,
    insertFragment,
    isBlock,
    isText,
    preCleanHtml,
    removeNodes,
} from "@udecode/plate-common";
import { ELEMENT_MENTION_INPUT } from "@udecode/plate-mention";
import { deserializeCsv } from "@udecode/plate-serializer-csv";
import { cleanDocx, isDocxContent } from "@udecode/plate-serializer-docx";
import { deserializeMd } from "@udecode/plate-serializer-md";
import { IMentionMatch, matchAtMention } from "@vanilla/utils";
import get from "lodash-es/get";
import { insertRichEmbedData } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbedData";
import { ELEMENT_CODE_BLOCK, ELEMENT_CODE_LINE } from "@udecode/plate-code-block";
import { insertCodeBlockData } from "@library/vanilla-editor/codeBlockPlugin/insertCodeBlockData";

export function insertMentionData(editor: MyEditor, data: DataTransfer) {
    const { insertNodes, insertData, point } = editor;
    const { files, types } = data;
    const text = data.getData("text/plain");
    const html = data.getData("text/html");
    const rtf = data.getData("text/rtf");

    const [parentNode, parentPath] = editor.selection ? getParentNode(editor, editor.selection.anchor.path) ?? [] : [];

    // If the data contains files, insert them as rich embeds
    if (files && files.length > 0 && !types.includes("text/plain")) {
        insertRichEmbedData(editor, data);
    }
    // parent node is a code block or code line
    else if ([ELEMENT_CODE_BLOCK, ELEMENT_CODE_LINE].includes(parentNode?.type as string)) {
        insertCodeBlockData(editor, data);
    }
    // parent node is a mention input, convert into a mention element
    else if (parentNode?.type === ELEMENT_MENTION_INPUT && parentPath) {
        const newElement = {
            type: ELEMENT_MENTION,
            name: text,
            children: [{ text: "" }],
        } as EElementOrText<MyValue>;
        removeNodes(editor, { at: parentPath });
        insertNodes([newElement, { text: " " }], { at: parentPath });
        const currentPoint = point(parentPath);
        const nextNode = getNextNode(editor, { at: currentPoint }) as any;
        focusEditor(editor, nextNode[1]);
    }
    // text is defined, so let's process that
    else if (text && parentNode?.type !== ELEMENT_MENTION_INPUT) {
        // The deserialized nodes from the pasted data that will be checked for mention elements
        let nodes: MyNode[] = [];

        // the pasted data has HTML
        if (html) {
            // convert the html into DOM elements
            const document = new DOMParser().parseFromString(preCleanHtml(html), "text/html");
            // determine if the pasted data is DocX content. if it is, then there should be rich text
            const isDocX = rtf && isDocxContent(document.body);
            // get the appropriate html to deserialize
            const htmlString = isDocX ? cleanDocx(html, rtf) : html;
            // deserialize the html
            nodes = deserializeHtml(htmlString) as MyNode[];
        }
        // the pasted data is plain text
        else {
            // deserialize as Markdown and CSV to see if there is a table
            const dsMD = deserializeMd(editor, text);
            const dsCSV = deserializeCsv(editor, { data: text });
            // assign the nodes, if there is a table, then it is CSV, otherwise use Markdown
            nodes = dsCSV ?? dsMD;
        }

        // if the nodes are not in a block element, then place them in the default one
        if (!isBlock(editor, nodes[0])) {
            nodes = [{ type: ELEMENT_DEFAULT, children: nodes }] as MyNode[];
        }

        // check the text of nodes that are not mention elements to see if there might be a mention
        nodes.forEach((node: MyNode, nodeIdx) => {
            const descendants = getNodeDescendants(node);
            for (let child of descendants) {
                const [childNode, childPath] = child;
                // if the node is text, let's evaluate further
                if (isText(childNode)) {
                    const parentPath = [nodeIdx, ...childPath.slice(0, -1)];
                    const parentPathString = `[${parentPath.join("].children[")}]`;
                    const parent = get(nodes, parentPathString);

                    // if the parent is a mention element, do not transform the text as this is pasted from another post
                    if (parent?.type === ELEMENT_MENTION) {
                        break;
                    }

                    const { text, ...childProps } = childNode;
                    let hasPossibleMention = false;

                    // the text might be multiple lines, so we need to split it and assess each line
                    const lines = childNode.text.split("\n");
                    const newChildren: MyNode[] = [];
                    lines.forEach((line, idx) => {
                        let atMention: IMentionMatch | null = null;
                        // if the line starts with an @, it might be a mention
                        if (line.startsWith(ELEMENT_MENTION)) {
                            hasPossibleMention = true;
                            atMention = matchAtMention(line, false, false);
                            newChildren.push(
                                {
                                    type: ELEMENT_MENTION,
                                    children: [{ text: "" }],
                                    name: atMention?.match,
                                },
                                { text: " " },
                            );
                        }
                        // there is no possible mention, return the line as text but include any formatting from original child node
                        else {
                            newChildren.push({ text: line, ...childProps });
                        }
                        // we need to make sure line breaks are added back in
                        if (idx < lines.length - 1) {
                            newChildren.push({ text: "\n" });
                        }
                    });

                    // if the text has a possible mention, replace the transformed child in the parent node
                    if (hasPossibleMention) {
                        parent.children.splice(childPath[childPath.length - 1], 1, ...newChildren);
                    }
                }
            }
        });

        // Insert the nodes as a fragment to the current location
        insertFragment(editor, nodes as Array<EElementOrText<MyValue>>);
    }
    // there is no text, continue with the editor's default method to insert data
    else {
        insertData(data);
    }
}
