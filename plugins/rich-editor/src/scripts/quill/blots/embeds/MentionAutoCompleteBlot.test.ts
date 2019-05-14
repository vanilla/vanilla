/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { expect } from "chai";
import MentionAutoCompleteBlot from "@rich-editor/quill/blots/embeds/MentionAutoCompleteBlot";
import { IMentionSuggestionData } from "@rich-editor/toolbars/pieces/MentionSuggestion";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";

describe("[MentionAutoCompleteBlot]", () => {
    it("can be finalized.", () => {
        const quill = setupTestQuill();

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
