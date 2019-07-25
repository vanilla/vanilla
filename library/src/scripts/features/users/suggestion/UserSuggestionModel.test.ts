/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IMentionSuggestionData } from "@rich-editor/toolbars/pieces/MentionSuggestion";
import { LoadStatus } from "@library/@types/api/core";
import UserSuggestionModel from "@library/features/users/suggestion/UserSuggestionModel";
import UserSuggestionActions from "@library/features/users/suggestion/UserSuggestionActions";
import { expect } from "chai";
import sinon from "sinon";
import { Moment } from "moment";
import moment from "moment";

type SortProviderTuple = [string[], string, string[]];
interface ISortTestData {
    input: IMentionSuggestionData[];
    search: string;
    expected: IMentionSuggestionData[];
}
function makeMentionSuggestion(username: string, dateLastActive: Moment | null = null): IMentionSuggestionData {
    return {
        name: username,
        domID: "",
        userID: 0,
        photoUrl: "",
        dateLastActive: dateLastActive ? dateLastActive.toISOString() : null,
    };
}

function createSortTestData(basicData: SortProviderTuple[]): ISortTestData[] {
    return basicData.map(data => {
        const [input, search, expected] = data;
        return {
            input: input.map(name => makeMentionSuggestion(name)),
            search,
            expected: expected.map(name => makeMentionSuggestion(name)),
        };
    });
}

describe("UserSuggestionModel", () => {
    let model: UserSuggestionModel;

    beforeEach(() => {
        model = new UserSuggestionModel();
    });

    describe("reducer()", () => {
        it("should return the initial state", () => {
            expect(model.reducer(undefined, {} as any)).deep.equals(model.initialState);
        });

        describe("LOAD_USERS_REQUEST", () => {
            it("can set the current value to loading", () => {
                const action = UserSuggestionActions.loadUsersACs.request({ username: "test" });
                expect(model.reducer(undefined, action).trie.getValue("test")).deep.equals({
                    status: LoadStatus.LOADING,
                });
            });

            it("invalidates the previous successful username", () => {
                const users = {
                    data: ["test", "test2"].map(name => makeMentionSuggestion(name)),
                    status: 200,
                };
                const successState = model.reducer(
                    undefined,
                    UserSuggestionActions.loadUsersACs.response(users, { username: "success" }),
                );
                expect(successState.lastSuccessfulUsername).equals("success");

                const clearedState = model.reducer(
                    successState,
                    UserSuggestionActions.loadUsersACs.request({ username: "test" }),
                );
                expect(clearedState.lastSuccessfulUsername).equals(null);
            });

            it("does not invalidate the previous successful username if the new one is a superset of that name", () => {
                const users = {
                    data: ["test", "test2"].map(name => makeMentionSuggestion(name)),
                    status: 200,
                };
                const successState = model.reducer(
                    undefined,
                    UserSuggestionActions.loadUsersACs.response(users, { username: "test" }),
                );
                expect(successState.lastSuccessfulUsername).equals("test");

                const nonClearedState = model.reducer(
                    successState,
                    UserSuggestionActions.loadUsersACs.request({ username: "test1" }),
                );
                expect(nonClearedState.lastSuccessfulUsername).equals("test");
            });
        });

        it("can handle LOAD_USERS_FAILURE", () => {
            let state = model.reducer(undefined, UserSuggestionActions.loadUsersACs.request({ username: "test" }));

            const consoleStub = sinon.stub(console, "error");

            const error = new Error("Failure!");
            state = model.reducer(state, UserSuggestionActions.loadUsersACs.error(error as any, { username: "test" }));

            expect(state.trie.getValue("test")).deep.equals({
                status: LoadStatus.ERROR,
                data: undefined,
                error,
            });
            consoleStub.restore();
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
                const setSuggestionState = model.reducer(
                    undefined,
                    UserSuggestionActions.setActiveAC("asdfasdfasdf", 134124),
                );
                state = model.reducer(
                    setSuggestionState,
                    UserSuggestionActions.loadUsersACs.response({ data: users, status: 200 }, { username: "test" }),
                );
            });

            it("Can save the user data", () => {
                const trieValue = state.trie.getValue("test");
                expect(trieValue.status).deep.equals(LoadStatus.SUCCESS);
                expect(trieValue.data!).deep.equals(users);
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
                const state = model.reducer(undefined, UserSuggestionActions.setActiveAC(domID, index));

                expect(state.activeSuggestionID).equals(domID);
                expect(state.activeSuggestionIndex).equals(index);
            });
        });
    });

    describe("sortSuggestions()", () => {
        const sortProvider: SortProviderTuple[] = [
            [["c", "b", "a", "z"], "f", ["a", "b", "c", "z"]],
            [["c", "b", "a", "z"], "z", ["z", "a", "b", "c"]], // Exact results come first
            [["stephane", "Stéphane", "z"], "ste", ["stephane", "Stéphane", "z"]], // Exact results come first
            [["Stephane", "stéphane", "z"], "sté", ["stéphane", "Stephane", "z"]], // Exact results come first
            [["testg", "testé", "testë", "testa", "teste"], "te", ["testa", "teste", "testé", "testë", "testg"]],
            [["testg", "testé", "testë", "testa", "teste"], "test", ["testa", "teste", "testé", "testë", "testg"]],
        ];

        createSortTestData(sortProvider).forEach(({ input, search, expected }, index) => {
            it(`Case ${index}`, () => {
                expect(UserSuggestionModel.sortSuggestions(input, search)).deep.eq(expected);
            });
        });

        it("sorts users active in the last 90 days to the top with exact matches first", () => {
            const currentTime = moment();
            const data = [
                makeMentionSuggestion("start-a-old2", currentTime.clone().subtract(100, "day")),
                makeMentionSuggestion("start-a-old1", currentTime.clone().subtract(100, "day")),
                makeMentionSuggestion("stárt-b-new1", currentTime.clone().subtract(1, "day")),
                makeMentionSuggestion("stârt-b-new2", currentTime.clone().subtract(1, "day")),
                makeMentionSuggestion("Start", currentTime.clone().subtract(1000, "day")),
            ];

            const expected = [
                makeMentionSuggestion("Start", currentTime.clone().subtract(1000, "day")), // Capitalization doesn't matter. It's "exact".
                makeMentionSuggestion("stárt-b-new1", currentTime.clone().subtract(1, "day")), // Even though the accents are different these users are newer.
                makeMentionSuggestion("stârt-b-new2", currentTime.clone().subtract(1, "day")),
                makeMentionSuggestion("start-a-old1", currentTime.clone().subtract(100, "day")),
                makeMentionSuggestion("start-a-old2", currentTime.clone().subtract(100, "day")),
            ];

            expect(UserSuggestionModel.sortSuggestions(data, "start")).deep.eq(expected);
        });
    });
});
