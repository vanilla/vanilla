/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ELEMENT_RICH_EMBED_CARD } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { MyEditor } from "@library/vanilla-editor/typescript";
import {
    PlateEditor,
    TNodeEntry,
    Value,
    getParentNode,
    getPluginType,
    isElement,
    removeNodes,
    unwrapNodes,
} from "@udecode/plate-common";
import { ELEMENT_PARAGRAPH } from "@udecode/plate-paragraph";
import { insertRichImage } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichImage";

export const normalizeRichEmbed = <V extends Value>(editor: PlateEditor<V>) => {
    const { normalizeNode } = editor;
    const paragraphType = getPluginType(editor, ELEMENT_PARAGRAPH);
    const embedCardType = getPluginType(editor, ELEMENT_RICH_EMBED_CARD);

    return ([node, path]: TNodeEntry) => {
        if (!isElement(node)) {
            return normalizeNode([node, path]);
        }

        if (node.type === embedCardType) {
            const imgSrc = node.url as string;

            // The image source is base64, let's turn it into a file
            if (imgSrc && imgSrc.includes("base64")) {
                // create the new image file from the blob parts
                const file = base64toFile(imgSrc);
                // insert it as a rich embed card
                editor.select({
                    anchor: { path: [...path, 0], offset: 0 },
                    focus: { path: [...path, 0], offset: 0 },
                });
                insertRichImage(editor as MyEditor, file);
                // remove the original node with the base64 src string
                removeNodes(editor, { at: path });
            }

            // Check to see if the rich embed is inside a paragraph. If it is, unwrap it.
            const parentNode = getParentNode(editor, path);
            if (parentNode && parentNode[0].type === paragraphType) {
                unwrapNodes(editor, {
                    at: parentNode[1],
                    match: { type: paragraphType },
                });
            }
        }

        normalizeNode([node, path]);
    };
};

/**
 * Generate a random file name
 * @param ext file extension
 * @param prefix file name prefix to place in from the random UID
 * @returns string
 */
function generateFileName(ext: string, prefix: string = "image"): string {
    const fileName = [prefix, Math.random().toString(16).slice(2), Math.random().toString(16).slice(2, 6)].join("-");
    return [fileName, ext].join(".");
}

/**
 * Convert a base64 string into a valid file
 *
 * Basic concept adapted from referenced article. Some functionality was updated to account for
 * deprecated functionality used within the article. ex. `atob()`
 *
 * @see https://ourcodeworld.com/articles/read/322/how-to-convert-a-base64-image-into-a-image-file-and-upload-it-with-an-asynchronous-form-using-jquery
 * @param base64string The base64
 * @returns File object
 */
function base64toFile(base64string: string) {
    // split the base64 string into data and content type
    const block = base64string.split(";");
    // get the content type of the image (ex: "image/png")
    const contentType = block[0].split(":")[1];
    // get the actual base64 data of the image
    const realData = block[1].split(",")[1];
    // generate a random filename
    const fileName = generateFileName(contentType.split("/")[1]);
    const sliceSize = 512;
    // read the base64 string into a buffer and convert into Uint8Array to be able to slice later
    const uintBuffer = Uint8Array.prototype.slice.call(Buffer.from(realData, "base64"));
    const bufferArrays: Uint8Array[] = [];
    // File() constructor only allows BlobPart[] for the fileBits argument
    // break up the buffer array into blob parts
    for (let offset = 0; offset < uintBuffer.length; offset += sliceSize) {
        const slice = uintBuffer.slice(offset, offset + sliceSize);
        bufferArrays.push(new Uint8Array(slice));
    }

    // create the new image file from the blob parts
    return new File(bufferArrays, fileName, { type: contentType });
}
