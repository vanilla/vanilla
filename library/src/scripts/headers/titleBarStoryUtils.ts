/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IMe } from "@library/@types/api/users";
import { testStoreState } from "@library/__tests__/testStoreState";
import localLogoUrl from "./titleBarStoryLogo.png";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";

export const mockRegisterUser: IMe = UserFixture.createMockUser({
    name: "Neena",
    userID: 1,
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
    countUnreadConversations: 1,
    emailConfirmed: true,
});

export const initialState = testStoreState({
    theme: {
        assets: {
            data: {
                logo: {
                    type: "image",
                    url: localLogoUrl as string,
                },
            },
        },
    },
});

export const initialStateWithMeboxVars = testStoreState({
    theme: {
        assets: {
            data: {
                logo: {
                    type: "image",
                    url: localLogoUrl as string,
                },
                variables: {
                    type: "json",
                    data: {
                        titleBar: {
                            meBox: {
                                withLabel: true,
                                withSeparator: true,
                            },
                        },
                    },
                },
            },
        },
    },
});

export const mockGuestUser: IMe = UserFixture.createMockUser({
    name: "test",
    userID: 0,
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
    countUnreadConversations: 1,
    emailConfirmed: false,
    roleIDs: [],
    roles: [],
});
