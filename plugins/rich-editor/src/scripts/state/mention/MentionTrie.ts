/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { IMentionSuggestionData } from "@rich-editor/components/toolbars/pieces/MentionSuggestion";
import { ILoadable } from "@library/@types/api";

export type IMentionValue = ILoadable<IMentionSuggestionData[]>;

export interface IMentionNode {
    children?: {
        [key: string]: IMentionNode;
    };
    value?: IMentionValue;
}

/**
 * A trie for storage of mention data.
 */
export default class MentionTrie {
    public MAX_PARTIAL_LOOKUP_ITERATIONS = 10;
    private root: IMentionNode = {};

    /**
     * Insert a value into a node for the word. This will overwrite whatever value the node already has
     *
     * @param word - The location in the trie.
     * @param value - The value for the node.
     */
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

    /**
     * Get a node for a given word.
     *
     * If no word is passed the root node will be returned.
     *
     * @param word - The word to lookup.
     */
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

    /**
     * Get the value out of a particular node.
     *
     * @param word - The word to lookup.
     */
    public getValue(word: string): IMentionValue | null {
        const node = this.getNode(word);
        return (node && node.value) || null;
    }

    /**
     * Lookup the value for a word using increasingly small substrings of the current string.
     *
     * Number of iterations is capped at MAX_PARTIAL_LOOKUP_ITERATIONS.
     *
     * ex. this.getValueFormPartials("test") will lookup for
     * - "test",
     * - "tes",
     * - "te",
     * - "t"
     *
     * And return immediately if it finds a result.
     */
    public getValueFromPartialsOfWord(word: string): IMentionValue | null {
        const startingLength = Math.min(this.MAX_PARTIAL_LOOKUP_ITERATIONS, word.length);
        for (let x = startingLength; x > 0; x--) {
            const substring = word.substring(0, x);
            const potentialValue = this.getValue(substring);
            if (potentialValue != null) {
                return potentialValue;
            }
        }

        return null;
    }
}
