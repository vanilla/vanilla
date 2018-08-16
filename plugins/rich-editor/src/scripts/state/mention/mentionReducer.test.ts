/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { expect } from "chai";
import mentionReducer, { initialState, sortSuggestions } from "./mentionReducer";
import { actions as mentionActions } from "./mentionActions";
// import sinon, { SinonSandbox } from "sinon";
import { IMentionSuggestionData } from "@rich-editor/components/toolbars/pieces/MentionSuggestion";

type SortProviderTuple = [string[], string, string[]];
interface ISortTestData {
    input: IMentionSuggestionData[];
    search: string;
    expected: IMentionSuggestionData[];
}
function makeMentionSuggestion(username: string): IMentionSuggestionData {
    return {
        name: username,
        domID: "",
        userID: 0,
        photoUrl: "",
        dateLastActive: "",
    };
}

function createSortTestData(basicData: SortProviderTuple[]): ISortTestData[] {
    return basicData.map(data => {
        const [input, search, expected] = data;
        return {
            input: input.map(makeMentionSuggestion),
            search,
            expected: expected.map(makeMentionSuggestion),
        };
    });
}

describe("sortSuggestions()", () => {
    const sortProvider: SortProviderTuple[] = [
        [["c", "b", "a", "z"], "f", ["a", "b", "c", "z"]],
        [["c", "b", "a", "z"], "z", ["z", "a", "b", "c"]], // Exact results come first
        [["Stephane", "Stéphane", "z"], "Ste", ["Stephane", "Stéphane", "z"]], // Exact results come first
        [["Stephane", "Stéphane", "z"], "Sté", ["Stéphane", "Stephane", "z"]], // Exact results come first
        [["testg", "testé", "testë", "testa", "teste", "testg"], "te", ["testa", "teste", "testé", "testë", "testg"]],
        [["testg", "testé", "testë", "testa", "teste", "testg"], "test", ["testa", "teste", "testé", "testë", "testg"]],
    ];

    createSortTestData(sortProvider).forEach(({ input, search, expected }, index) => {
        it(`Case ${index}`, () => {
            expect(sortSuggestions(input, search));
        });
    });
});

describe("mentionReducer", () => {
    // const sandbox: SinonSandbox = sinon.createSandbox();
    // afterEach(() => sandbox.restore());

    it("should return the initial state", () => {
        expect(mentionReducer(undefined, {} as any)).deep.equals(initialState);
    });

    describe("LOAD_USERS_REQUEST", () => {
        it("can set the current value to pending", () => {
            // const response = "";
            // sandbox.stub(api, "get");

            const action = mentionActions.loadUsersRequest("test");
            expect(mentionReducer(undefined, action).usersTrie.getValue("test")).deep.equals({ status: "PENDING" });
        });

        it("invalidates the previous successful username", () => {
            const users = ["test", "test2"].map(makeMentionSuggestion);
            const successState = mentionReducer(undefined, mentionActions.loadUsersSuccess("success", users));
            expect(successState.lastSuccessfulUsername).equals("success");

            const clearedState = mentionReducer(successState, mentionActions.loadUsersRequest("test"));
            expect(clearedState.lastSuccessfulUsername).equals(null);
        });

        it("does not invalidate the previous successful username if the new one is a superset of that name", () => {
            const users = ["test", "test2"].map(makeMentionSuggestion);
            const successState = mentionReducer(undefined, mentionActions.loadUsersSuccess("test", users));
            expect(successState.lastSuccessfulUsername).equals("test");

            const nonClearedState = mentionReducer(successState, mentionActions.loadUsersRequest("test1"));
            expect(nonClearedState.lastSuccessfulUsername).equals("test");
        });
    });

    it("can handle LOAD_USERS_FAILURE", () => {
        let state = mentionReducer(undefined, mentionActions.loadUsersRequest("test"));

        const error = new Error("Failure!");
        state = mentionReducer(state, mentionActions.loadUsersFailure("test", error as any));

        expect(state.usersTrie.getValue("test")).deep.equals({
            status: "FAILED",
            users: null,
            error,
        });
    });

    describe("LOAD_USERS_SUCCESS", () => {
        const domID = "mention-domId";
        const users = [
            {
                name: "test",
                domID,
                userID: 0,
                photoUrl: "",
                dateLastActive: "",
            },
        ];

        let state;

        before(() => {
            const setSuggestionState = mentionReducer(
                undefined,
                mentionActions.setActiveSuggestion("asdfasdfasdf", 134124),
            );
            state = mentionReducer(setSuggestionState, mentionActions.loadUsersSuccess("test", users));
        });

        it("Can save the user data", () => {
            const trieValue = state.usersTrie.getValue("test");
            expect(trieValue.status).deep.equals("SUCCESSFUL");
            expect(trieValue.users!).deep.equals(users);
        });

        it("can sets the last and current useranmes", () => {
            expect(state.lastSuccessfulUsername).equals("test");
        });

        it("sets the active selection index and id", () => {
            expect(state.activeSuggestionID).equals(domID);
            expect(state.activeSuggestionIndex).equals(0);
        });
    });

    describe("SET_ACTIVE_SUGGESTION", () => {
        it("sets the active selection index and id", () => {
            const domID = "asdfasdfasdfasd4224f";
            const index = 4134;
            const state = mentionReducer(undefined, mentionActions.setActiveSuggestion(domID, index));

            expect(state.activeSuggestionID).equals(domID);
            expect(state.activeSuggestionIndex).equals(index);
        });
    });
});
