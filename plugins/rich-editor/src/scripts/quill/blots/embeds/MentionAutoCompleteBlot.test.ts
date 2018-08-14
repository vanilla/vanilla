/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import Quill from "quill/core";
import MentionAutoCompleteBlot from "./MentionAutoCompleteBlot";
import { expect } from "chai";
import { IMentionSuggestionData } from "@rich-editor/components/toolbars/pieces/MentionSuggestion";
import registerQuill from "@rich-editor/quill/registerQuill";

describe("[MentionAutoCompleteBlot]", () => {
    it("can be finalized.", () => {
        registerQuill();
        const quill = new Quill(document.body);

        const data: IMentionSuggestionData = {
            userID: 1,
            name: "complete",
            photoUrl: "https://github.com",
            dateLastActive: "",
            domID: "asdf",
        };

        quill.setContents([
            {
                insert: "@incomplete",
                attributes: { "mention-autocomplete": true },
            },
        ]);

        const blot = (quill.scroll as any).descendant(MentionAutoCompleteBlot, 0)[0] as MentionAutoCompleteBlot;
        blot.finalize(data);

        const expected = [{ insert: { mention: { name: "complete", userID: 1 } } }, { insert: "\n" }];
        quill.update();

        expect(quill.getContents().ops).deep.equals(expected);
    });
});
