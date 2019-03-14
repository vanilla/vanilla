/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IUserSuggestion } from "@library/features/users/suggestion/IUserSuggestion";
import UserSuggestionActions from "@library/features/users/suggestion/UserSuggestionActions";
import { expect } from "chai";

function makeMentionSuggestion(username: string): IUserSuggestion {
    return {
        name: username,
        domID: "",
        userID: 0,
        photoUrl: "",
        dateLastActive: "",
    };
}

describe("UserSuggestionActions.filterSuggestions()", () => {
    const SHORTER_STRINGS = ["tes", "te", "t"].map(makeMentionSuggestion);
    const LONGER_STRINGS = ["testi", "testing", "test ias.df asidf", "test ias.df asidf12431`234"].map(
        makeMentionSuggestion,
    );
    const NON_MATCHING = ["asdfasd", "123123", "asd.asdfk50", "0"].map(makeMentionSuggestion);
    const MATCHING = ["test", "téšt", "téšt1234@asd..."].map(makeMentionSuggestion);

    const initalSuggestions = [...SHORTER_STRINGS, ...LONGER_STRINGS, ...NON_MATCHING, ...MATCHING];
    const lookup = "test";

    let results: IUserSuggestion[] = [];

    before(() => {
        results = UserSuggestionActions.filterSuggestions(initalSuggestions, lookup);
    });

    it("removes strings that are shorter than the current string", () => {
        SHORTER_STRINGS.forEach(short => {
            expect(results).not.to.contain(short);
        });
    });
    it("keeps strings that are longer than the current string", () => {
        LONGER_STRINGS.forEach(long => {
            expect(results).to.contain(long);
        });
    });
    it("removes non-matching string", () => {
        NON_MATCHING.forEach(nonMatch => {
            expect(results).not.to.contain(nonMatch);
        });
    });
    it("allows matching string", () => {
        LONGER_STRINGS.forEach(long => {
            expect(results).to.contain(long);
        });
    });
});
