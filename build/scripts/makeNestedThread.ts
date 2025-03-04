/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { uuidv4 } from "@vanilla/utils";

/**
UPDATE GDN_Comment
SET InsertUserID = FLOOR(2 + (RAND() * 11))
WHERE ParentRecordID = TARGET_COMMENT;
*/

let DEPTH = 3;
let ROOT_SIZE = 90;
let MAX_CHILDREN = 50;
let TARGET_COMMENT = 1;
let currentDepth = 1;
let ACCESS_TOKEN: string | undefined = undefined;

async function createComment(body) {
    if (process.env.NODE_TLS_REJECT_UNAUTHORIZED !== "0") {
        console.warn("NODE_TLS_REJECT_UNAUTHORIZED is not set to 0");
        console.warn("Run `export NODE_TLS_REJECT_UNAUTHORIZED=0` before running this script");
    }
    return await fetch("https://dev.vanilla.local/api/v2/comments", {
        method: "POST",
        headers: {
            accept: "application/json",
            "Content-Type": "application/json",
            Authorization: `Bearer ${ACCESS_TOKEN}`,
        },
        body: JSON.stringify({
            body: "Here is a api generated comment",
            discussionID: TARGET_COMMENT,
            parentRecordType: "discussion",
            parentRecordID: TARGET_COMMENT,
            format: "text",
            ...body,
        }),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Failed to create comment");
            }
            return response.json();
        })
        .catch((error) => console.error(error));
}

const getRandomInt = (valueMin, valueMax) => Math.floor(Math.random() * (valueMax - valueMin + 1) + valueMin);

const randomlyCreateChildren = async (parentCommentID, currentDepth, bodyString?) => {
    // Random chance to create children
    if (Math.round(Math.random()) && currentDepth <= DEPTH) {
        const numberOfChildren = getRandomInt(1, MAX_CHILDREN);
        //eslint-disable-next-line
        console.log(`Creating ${numberOfChildren} children at depth ${currentDepth}`);
        for (let child = 0; child < numberOfChildren; child++) {
            const id = uuidv4();
            const response = await createComment({
                body: `Reply to ${bodyString}.\nComment ID: ${id}.\nComment depth: ${currentDepth}`,
                parentCommentID: parentCommentID,
            });
            //eslint-disable-next-line
            console.log(
                `Created depth ${currentDepth} comment with ID: ${response.commentID} and parent ID: ${parentCommentID}`,
            );
            await randomlyCreateChildren(response.commentID, currentDepth + 1, id);
        }
    }
};

const createThread = async () => {
    for (let root = 0; root < ROOT_SIZE; root++) {
        const id = uuidv4();
        const response = await createComment({ body: `Top level comment: ${id}` });
        //eslint-disable-next-line
        console.log(`Created root comment ${root} with ID: ${response.commentID}`);
        await randomlyCreateChildren(response.commentID, currentDepth + 1, id);
    }
};

function main() {
    const idIndex = process.argv.indexOf("--discussionID");
    const rootIndex = process.argv.indexOf("--root");
    const maxChildIndex = process.argv.indexOf("--max-children");
    const maxDepthIndex = process.argv.indexOf("--max-depth");
    const tokenIndex = process.argv.indexOf("--token");

    if (process.env.VANILLA_API_ACCESS_TOKEN || tokenIndex !== -1) {
        ACCESS_TOKEN = process.env.VANILLA_API_ACCESS_TOKEN || process.argv[tokenIndex + 1];
        if (idIndex > -1) {
            if (rootIndex > -1) {
                ROOT_SIZE = Number(process.argv[rootIndex + 1]);
            }
            if (maxChildIndex > -1) {
                MAX_CHILDREN = Number(process.argv[maxChildIndex + 1]);
            }
            if (maxDepthIndex > -1) {
                DEPTH = Number(process.argv[maxDepthIndex + 1]);
            }

            TARGET_COMMENT = Number(process.argv[idIndex + 1]);
            createThread();
        } else {
            console.error("Please provide a discussionID with the --discussionID flag");
        }
    } else {
        console.error("Please provide a token for this request with the --token flag");
        console.error(
            "Alternatively, run `export VANILLA_API_ACCESS_TOKEN=YOUR_TOKEN_HERE` before running this script",
        );
    }
}

main();
