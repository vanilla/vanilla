/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { deserializeHtml as plateDeserializeHtml } from "@udecode/plate-common";
import { logError } from "@vanilla/utils";
import { MyValue } from "@library/vanilla-editor/typescript";
import { createVanillaEditor } from "./createVanillaEditor";

/**
 * Pass this method HTML and it should return it back valid Rich2
 */
export function deserializeHtml(html: string): MyValue | undefined {
    const editor = createVanillaEditor();
    if (!html) {
        logError("html not provided");
        return;
    }

    let validHTML = html;

    // Parse the html string into a DOM object to evaluate nodes
    const parser = new DOMParser();
    const parsedHTML = parser.parseFromString(html, "text/html");
    // Only evaluate the top level child nodes found within the body
    const nodes = Array.from(parsedHTML.body.childNodes);

    // Invalid HTML nodes exist in the list. Let's fix it, Felix!
    if (nodes.filter((node) => !validNode(node))) {
        // group the nodes before wrapping properly
        const nodeGroups: Array<ChildNode | ChildNode[]> = [];
        let tmpGroup: ChildNode[] = [];

        nodes.forEach((node, idx) => {
            // Node is valid. Add the temp group and the node to the list and reset the temp group
            if (validNode(node)) {
                nodeGroups.push(tmpGroup, node);
                tmpGroup = [];
                return;
            }

            // Add the node to the temp group
            tmpGroup.push(node);

            // Add the temp group and reset if the node and it's next sibling is a BR element or we have reached the end of the list
            if (
                idx === nodes.length - 1 ||
                (node instanceof HTMLBRElement && node.nextSibling instanceof HTMLBRElement)
            ) {
                nodeGroups.push(tmpGroup);
                tmpGroup = [];
            }
        });

        // Now that we have grouped invalid nodes, let's wrap them in paragraphs and build valid HTML
        const tmpBody = document.createElement("body");
        const nodesLastIdx = nodeGroups.length - 1;
        nodeGroups.forEach((item, itemIdx) => {
            // The current item is a valid node, let's just add it to the temp body
            if (!Array.isArray(item)) {
                tmpBody.append(item);
                return;
            }

            // The current item contains only an image and therefore should not be wrapped in a paragraph
            if (item.length === 1 && item[0] instanceof HTMLImageElement) {
                tmpBody.append(item[0]);
                return;
            }

            // The current item is an array of elements, let's place them inside a paragraph
            if (item.length > 0) {
                const itemLastIdx = item.length - 1;
                const tmpParagraph = document.createElement("p");
                item.forEach((child, childIdx) => {
                    // We don't want to add BR elements that created hard line breaks
                    const addToDOM = !(
                        child instanceof HTMLBRElement &&
                        ((childIdx === 0 && itemIdx > 0) || (childIdx === itemLastIdx && itemIdx < nodesLastIdx))
                    );

                    if (addToDOM) {
                        tmpParagraph.append(child);
                    }
                });

                // Add the paragraph to the temp body
                tmpBody.append(tmpParagraph);
            }
        });

        // the temp body innerHTML is now our valid HTML
        validHTML = tmpBody.innerHTML;
    }

    return plateDeserializeHtml(editor, {
        element: validHTML,
    }) as MyValue;
}

/**
 * Determine if the node is valid as stand alone HTML
 */
export function validNode(node) {
    return !["Text", "HTMLBRElement", "HTMLElement", "HTMLImageElement"].includes(node.constructor.name);
}
