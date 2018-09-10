/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import MentionTrie, { IMentionNode, IMentionValue } from "./MentionTrie";
import { expect } from "chai";
import { LoadStatus } from "@library/@types/api";

const LOADING_VALUE: IMentionValue = {
    status: LoadStatus.LOADING,
};

const SUCCESSFUL_VALUE: IMentionValue = {
    status: LoadStatus.SUCCESS,
    data: [],
};

describe("MentionTrie", () => {
    describe("insert", () => {
        it("can insert values", () => {
            const trie = new MentionTrie();
            const expected: IMentionNode = {
                children: {
                    t: {
                        children: {
                            e: {
                                children: {
                                    s: {
                                        children: {
                                            t: {
                                                children: {},
                                                value: {
                                                    status: LoadStatus.LOADING,
                                                },
                                            },
                                        },
                                    },
                                    f: {
                                        children: {
                                            t: {
                                                children: {},
                                                value: {
                                                    status: LoadStatus.SUCCESS,
                                                    data: [],
                                                },
                                            },
                                        },
                                    },
                                },
                            },
                        },
                    },
                },
            };

            trie.insert("test", LOADING_VALUE);

            trie.insert("teft", SUCCESSFUL_VALUE);

            expect(trie.getNode()).to.deep.equal(expected);
        });

        it("Does not invalidate a parent value with a child insertion", () => {
            const trie = new MentionTrie();
            trie.insert("t", LOADING_VALUE);
            trie.insert("t", SUCCESSFUL_VALUE);
            expect(trie.getValue("t")).deep.equals(SUCCESSFUL_VALUE);
            trie.insert("te", LOADING_VALUE);
            trie.insert("te", SUCCESSFUL_VALUE);
            expect(trie.getValue("t")).deep.equals(SUCCESSFUL_VALUE);
            expect(trie.getValue("te")).deep.equals(SUCCESSFUL_VALUE);
        });
    });

    describe("getValue", () => {
        it("can retrieve a value", () => {
            const trie = new MentionTrie();
            trie.insert("test", LOADING_VALUE);
            trie.insert("teft", SUCCESSFUL_VALUE);

            expect(trie.getValue("test")).to.deep.equal(LOADING_VALUE);
            expect(trie.getValue("teft")).to.deep.equal(SUCCESSFUL_VALUE);
        });

        it("returns null if its value cannot be found", () => {
            const trie = new MentionTrie();
            trie.insert("test", LOADING_VALUE);
            trie.insert("teft", SUCCESSFUL_VALUE);

            expect(trie.getValue("t")).to.eq(null);
            expect(trie.getValue("te")).to.eq(null);
            expect(trie.getValue("tes")).to.eq(null);
        });

        it("will overwrite an existing value", () => {
            const trie = new MentionTrie();
            trie.insert("test", LOADING_VALUE);
            trie.insert("test", SUCCESSFUL_VALUE);

            expect(trie.getValue("test")).to.deep.eq(SUCCESSFUL_VALUE);
        });
    });
});
