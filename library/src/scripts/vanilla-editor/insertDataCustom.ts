/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
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
import { getMeta } from "@library/utility/appUtils";
import {
    cleanTableRowspanColspan,
    needsRowspanColspanCleaning,
} from "@library/vanilla-editor/plugins/tablePlugin/tableUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";

// We should only have one override of insertData (if there are multiple, only the last one listed will work)
// This needs to cover all cases, but parts can be disabled as needed (e.g. if mentions are not enabled)
export default function insertDataCustom(editor: MyEditor, data: DataTransfer) {
    // Currently we allow  mentions to be disabled, but not code blocks or rich embeds
    // This could be added in future, in which case these should no longer be hardcoded to true
    const supportsRichEmbed = true;
    const supportsCodeBlock = true;
    const supportsMentions = getMeta("ui.userMentionsEnabled", true);
    const isRichTableEnabled = getMeta("featureFlags.RichTable.Enabled", false);

    const { insertNodes, point } = editor;
    const { files, types } = data;
    const text = data.getData("text/plain");
    const html = data.getData("text/html");
    const rtf = data.getData("text/rtf");

    const [parentNode, parentPath] = editor.selection ? getParentNode(editor, editor.selection.anchor.path) ?? [] : [];

    // If the data contains files, insert them as rich embeds
    if (files && files.length > 0 && !types.includes("text/plain") && supportsRichEmbed) {
        insertRichEmbedData(editor, data);
    }

    // parent node is a code block or code line
    else if ([ELEMENT_CODE_BLOCK, ELEMENT_CODE_LINE].includes(parentNode?.type as string) && supportsCodeBlock) {
        insertCodeBlockData(editor, data);
    }

    // Remaining conditions handle mentions, and any other text/html content
    // If mentions are disabled, it will insert a link to the user's profile instead of a real mention element

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
    // handle anything else, most likely plain text or html
    else {
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
        else if (text) {
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
                        continue;
                    }

                    const { text, ...childProps } = childNode;
                    let hasPossibleMention = false;

                    // the text might be multiple lines, so we need to split it and assess each line
                    const lines = childNode.text.split("\n");
                    const newChildren: MyNode[] = [];

                    lines.forEach((line, idx) => {
                        let atMention: IMentionMatch | null = null;

                        if (line.includes(ELEMENT_MENTION) && supportsMentions) {
                            // Assumes mentions can contain spaces and quotes, but no punctuation
                            const matchesArray = [...line.matchAll(/@[A-Za-z0-9 "'"]+/g)];

                            if (matchesArray.length === 0) {
                                // just add the text, no mention element
                                newChildren.push({ text: line, ...childProps });
                            } else {
                                // At least one possible mention
                                const beforeMention = line.substring(0, matchesArray[0].index);

                                newChildren.push({ text: beforeMention });

                                matchesArray.forEach((match, i) => {
                                    const start = match.index;
                                    const end = match.index + match[0].length;

                                    const isLastMention = i === matchesArray.length - 1;
                                    const startOfNextMention = isLastMention ? line.length : matchesArray[i + 1].index;

                                    // Text of mention itself, there may be other text between this and any other mentions after this
                                    const myNewMentionText = line.substring(start, end);

                                    const textBeforeNextMention = line.substring(end, startOfNextMention);

                                    atMention = matchAtMention(myNewMentionText, false, false);

                                    if (!!atMention && atMention.match) {
                                        hasPossibleMention = true;

                                        newChildren.push(
                                            {
                                                type: ELEMENT_MENTION,
                                                children: [{ text: "" }],
                                                name: atMention?.match,
                                                id: uniqueIDFromPrefix(atMention.match),
                                            },
                                            { text: " " },
                                            { text: textBeforeNextMention ?? "" },
                                        );
                                    } else {
                                        // just add the text, no mention element
                                        newChildren.push({
                                            text: myNewMentionText + textBeforeNextMention,
                                            ...childProps,
                                        });
                                    }
                                });
                            }
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

        if (isRichTableEnabled && needsRowspanColspanCleaning(nodes as Array<EElementOrText<MyValue>>)) {
            nodes = cleanTableRowspanColspan(editor, nodes as Array<EElementOrText<MyValue>>);
        }

        // Insert the nodes as a fragment to the current location
        insertFragment(editor, nodes as Array<EElementOrText<MyValue>>);
    }
}
