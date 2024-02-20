/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { mockAPI } from "@library/__tests__/utility";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { Reactions } from "@library/reactions/Reactions";
import { IReactionsProps, IRecordReaction } from "@library/reactions/Reactions.types";
import { STORY_DATE_ENDS, STORY_DATE_STARTS, STORY_IMAGE, STORY_REACTIONS } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen } from "@testing-library/react";

const mockAdapter = mockAPI();
const REACTIONS_URL = "/discussions/1/reactions";
const USERS_URL = "/discussions/1/reactions?type=TestReaction";

const MOCK_REACTION: IReaction = {
    tagID: 1,
    urlcode: "TestReaction",
    name: "Test Reaction",
    class: "Positive",
    hasReacted: false,
    count: 3,
    reactionValue: 1,
};

const MOCK_REACTION_RESPONSE: IRecordReaction[] = [
    {
        recordType: "Discussion",
        recordID: 1,
        tagID: 1,
        userID: 1,
        dateInserted: STORY_DATE_STARTS,
        user: {
            userID: 1,
            name: "Test User 1",
            photoUrl: STORY_IMAGE,
            dateLastActive: STORY_DATE_ENDS,
        },
        reactionType: {
            tagID: 1,
            urlcode: "TestReaction",
            name: "Test Reaction",
            class: "Positive",
        },
    },
    {
        recordType: "Discussion",
        recordID: 1,
        tagID: 1,
        userID: 2,
        dateInserted: STORY_DATE_STARTS,
        user: {
            userID: 2,
            name: "Test User 2",
            photoUrl: STORY_IMAGE,
            dateLastActive: STORY_DATE_ENDS,
        },
        reactionType: {
            tagID: 1,
            urlcode: "TestReaction",
            name: "Test Reaction",
            class: "Positive",
        },
    },
    {
        recordType: "Discussion",
        recordID: 1,
        tagID: 1,
        userID: 3,
        dateInserted: STORY_DATE_STARTS,
        user: {
            userID: 3,
            name: "Test User 3",
            photoUrl: STORY_IMAGE,
            dateLastActive: STORY_DATE_ENDS,
        },
        reactionType: {
            tagID: 1,
            urlcode: "TestReaction",
            name: "Test Reaction",
            class: "Positive",
        },
    },
];

const MOCK_USER_LIST: IUserFragment[] = [
    {
        userID: 1,
        name: "Test User 1",
        photoUrl: STORY_IMAGE,
        dateLastActive: STORY_DATE_ENDS,
    },
    {
        userID: 2,
        name: "Test User 2",
        photoUrl: STORY_IMAGE,
        dateLastActive: STORY_DATE_ENDS,
    },
    {
        userID: 3,
        name: "Test User 3",
        photoUrl: STORY_IMAGE,
        dateLastActive: STORY_DATE_ENDS,
    },
];

// Wrapper with QueryClientProvider for testing hooks
function queryClientWrapper() {
    const queryClient = new QueryClient();
    const Wrapper = ({ children }) => (
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider state={{ users: { current: UserFixture.adminAsCurrent } }}>{children}</TestReduxProvider>
        </QueryClientProvider>
    );

    return Wrapper;
}

// Wrapper with QueryClientProvider for testing hooks
function renderComponent(props: Partial<IReactionsProps> = {}) {
    const QueryClientWrapper = queryClientWrapper();

    const componentProps: IReactionsProps = {
        recordType: props.recordType ?? "discussion",
        recordID: props.recordID ?? 1,
        reactions: props.reactions ?? STORY_REACTIONS,
    };

    return render(
        <QueryClientWrapper>
            <TestReduxProvider state={{ users: { current: UserFixture.adminAsCurrent } }}>
                <PermissionsFixtures.AllPermissions>
                    <Reactions {...componentProps} />
                </PermissionsFixtures.AllPermissions>
            </TestReduxProvider>
        </QueryClientWrapper>,
    );
}

describe("Reactions Component", () => {
    afterEach(() => {
        mockAdapter.reset();
    });

    it("renders reactions as buttons", () => {
        renderComponent();
        expect(screen.getAllByRole("button").length).toBe(5);

        STORY_REACTIONS.forEach((reaction) => {
            const reactionBtn = screen.getByLabelText(reaction.name);
            expect(reactionBtn).toBeDefined();
            if (reaction.count > 0) {
                expect(reactionBtn).toHaveTextContent(reaction.count.toString());
            } else {
                expect(reactionBtn).not.toHaveTextContent("0");
            }
        });
    });

    it("styles an active reaction for a discussion differently", () => {
        renderComponent();

        let isActiveCount = 0;

        STORY_REACTIONS.forEach((reaction) => {
            const reactionBtn = screen.getByLabelText(reaction.name);
            if (reaction.hasReacted) {
                expect(reactionBtn).toHaveStyle("background-color: rgb(3, 125, 188)");
                isActiveCount += 1;
            } else {
                expect(reactionBtn).toHaveStyle("background-color: rgb(255, 255, 255)");
            }
        });

        expect(isActiveCount).toBe(1);
    });
});
