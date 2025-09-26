/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { mockGuestUser, mockRegisterUser, initialState } from "@library/headers/titleBarStoryUtils";
import getStore from "@library/redux/getStore";
import { MeBoxDesktop } from "@library/headers/mebox/MeBoxDesktop";
import { ReduxThemeContextProvider } from "@library/theming/Theme.context";
import { Provider } from "react-redux";
import { MemoryRouter } from "react-router";
import { TitleBarParamContextProvider } from "@library/headers/TitleBar.ParamContext";

export default {
    title: "System Components/MeBoxDesktop",
};

function VanillaBlueWrapper({ children }) {
    return (
        <TitleBarParamContextProvider>
            <div style={{ backgroundColor: "#037DBC", padding: "1rem", display: "flex", justifyContent: "center" }}>
                {children}
            </div>
        </TitleBarParamContextProvider>
    );
}

export function Registered() {
    return (
        <VanillaBlueWrapper>
            <MemoryRouter>
                <Provider store={getStore(initialState, true)}>
                    <ReduxThemeContextProvider>
                        <CurrentUserContextProvider currentUser={mockRegisterUser}>
                            <MeBoxDesktop />
                        </CurrentUserContextProvider>
                    </ReduxThemeContextProvider>
                </Provider>
            </MemoryRouter>
        </VanillaBlueWrapper>
    );
}

export function Anonymous() {
    return (
        <VanillaBlueWrapper>
            <MemoryRouter>
                <Provider store={getStore(initialState, true)}>
                    <ReduxThemeContextProvider>
                        <CurrentUserContextProvider currentUser={mockGuestUser}>
                            <MeBoxDesktop />
                        </CurrentUserContextProvider>
                    </ReduxThemeContextProvider>
                </Provider>
            </MemoryRouter>
        </VanillaBlueWrapper>
    );
}
