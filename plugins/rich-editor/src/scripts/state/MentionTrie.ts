/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { IMentionUser } from "@dashboard/apiv2";

interface ISuccessValue {
    status: "SUCCESSFUL";
    users: IMentionUser[];
}

interface IFailureValue {
    status: "FAILED";
    users?: null;
    error: Error;
}

interface IPendingValue {
    status: "PENDING";
}

export type IMentionValue = ISuccessValue | IFailureValue | IPendingValue;

export interface IMentionNode {
    children?: {
        [key: string]: IMentionNode;
    };
    value?: IMentionValue;
}

export default class MentionTrie {
    private root: IMentionNode = {};

    public insert(word: string, value: IMentionValue): void {
        let current = this.root;

        for (let i = 0; i < word.length; i++) {
            const letter = word[i];
            if (!current.children) {
                current.children = {};
            }

            if (!(letter in current.children)) {
                const contents: IMentionNode = i !== word.length - 1 ? {} : { children: {} };
                current.children[letter] = contents;
            }

            current = current.children[letter];
        }

        current.value = value;
    }

    public getNode(word?: string): IMentionNode | null {
        let node = this.root;
        if (word === undefined) {
            return node;
        }

        for (let i = 0; i < word.length; i++) {
            const char = word.charAt(i);
            if (node.children && node.children[char]) {
                node = node.children[char];
            } else {
                return null;
            }
        }
        return node;
    }

    public getValue(word?: string): IMentionValue | null {
        const node = this.getNode(word);
        return (node && node.value) || null;
    }
}
