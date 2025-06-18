/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { initialState, mockGuestUser, mockRegisterUser } from "@library/headers/titleBarStoryUtils";
import getStore from "@library/redux/getStore";
import { ReduxThemeContextProvider } from "@library/theming/Theme.context";
import { Provider } from "react-redux";
import { MemoryRouter } from "react-router";
import TitleBarFragment from "./TitleBarFragment.template";
import "./TitleBarFragment.template.css";
import { TitleBarParamContextProvider } from "@library/headers/TitleBar.ParamContext";

export default {
    title: "Fragments/TitleBar",
};

const storyNavigationItems = [
    {
        name: "Item One",
        id: "item-one",
        url: "#",
        children: [
            {
                name: "Item Two One",
                id: "item-two-one",
                url: "#",
            },
            {
                name: "Item Two Two",
                id: "item-two-two",
                url: "#",
            },
            {
                name: "Item Two Three",
                id: "item-two-three",
                url: "#",
            },
        ],
    },
    {
        name: "Item Two",
        id: "item-two",
        url: "#",
    },
    {
        name: "Item Three",
        id: "item-three",
        url: "#",
        children: [
            {
                name: "Item Three One",
                id: "item-three-one",
                url: "#",
            },
            {
                name: "Item Three Two",
                id: "item-three-two",
                url: "#",
            },
            {
                name: "Item Three Three",
                id: "item-three-three",
                url: "#",
            },
        ],
    },
];

export function Template() {
    return (
        <MemoryRouter>
            <Provider store={getStore(initialState, true)}>
                <ReduxThemeContextProvider>
                    <CurrentUserContextProvider currentUser={mockRegisterUser}>
                        <TitleBarParamContextProvider navigation={{ alignment: "left", items: storyNavigationItems }}>
                            <TitleBarFragment navigation={{ items: storyNavigationItems }} />
                        </TitleBarParamContextProvider>
                    </CurrentUserContextProvider>
                </ReduxThemeContextProvider>
            </Provider>
        </MemoryRouter>
    );
}
