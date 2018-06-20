/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import MentionTrie, { IMentionNode, IMentionValue } from "./MentionTrie";
import { expect } from "chai";

const PENDING_VALUE: IMentionValue = {
    status: "PENDING",
};

const SUCCESSFUL_VALUE: IMentionValue = {
    status: "SUCCESSFUL",
    users: [],
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
                                                    status: "PENDING",
                                                },
                                            },
                                        },
                                    },
                                    f: {
                                        children: {
                                            t: {
                                                children: {},
                                                value: {
                                                    status: "SUCCESSFUL",
                                                    users: [],
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

            trie.insert("test", PENDING_VALUE);

            trie.insert("teft", SUCCESSFUL_VALUE);

            expect(trie.getNode()).to.deep.equal(expected);
        });
    });

    describe("getValue", () => {
        it("can retrieve a value", () => {
            const trie = new MentionTrie();
            trie.insert("test", PENDING_VALUE);
            trie.insert("teft", SUCCESSFUL_VALUE);

            expect(trie.getValue("test")).to.deep.equal(PENDING_VALUE);
            expect(trie.getValue("teft")).to.deep.equal(SUCCESSFUL_VALUE);
        });

        it("returns null if its value cannot be found", () => {
            const trie = new MentionTrie();
            trie.insert("test", PENDING_VALUE);
            trie.insert("teft", SUCCESSFUL_VALUE);

            expect(trie.getValue("t")).to.eq(null);
            expect(trie.getValue("te")).to.eq(null);
            expect(trie.getValue("tes")).to.eq(null);
        });

        it("will overwrite an existing value", () => {
            const trie = new MentionTrie();
            trie.insert("test", PENDING_VALUE);
            trie.insert("test", SUCCESSFUL_VALUE);

            expect(trie.getValue("test")).to.deep.eq(SUCCESSFUL_VALUE);
        });
    });
});
