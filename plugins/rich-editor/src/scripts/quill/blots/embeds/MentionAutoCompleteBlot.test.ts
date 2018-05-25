/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import Quill from "../../../quill";
import Parchment from "parchment";
import MentionAutoCompleteBlot from "./MentionAutoCompleteBlot";
import { IMentionData } from "../../../editor/MentionSuggestion";
import { expect } from "chai";

describe("[MentionAutoCompleteBlot]", () => {
    it("can be finalized.", () => {
        const quill = new Quill(document.body);

        const data: IMentionData = {
            userID: 1,
            name: "complete",
            photoUrl: "https://github.com",
            uniqueID: "asdf",
            onMouseEnter: () => {
                return;
            },
        };

        const newLine = { insert: "\n" };

        quill.setContents([
            {
                insert: "@incomplete",
                attributes: { "mention-autocomplete": true },
            },
        ]);

        const blot = (quill.scroll as any).descendant(MentionAutoCompleteBlot, 0)[0] as MentionAutoCompleteBlot;
        const finalized = blot.finalize(data);

        const expected = [{ insert: { mention: { name: "complete", userID: 1 } } }, { insert: "\n" }];
        quill.update();

        expect(quill.getContents().ops).deep.equals(expected);
    });
});
