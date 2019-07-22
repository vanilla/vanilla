/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { addDecorator } from "@storybook/react";
import Backgrounds from "@library/layout/Backgrounds";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import { ensureScript } from "@vanilla/dom-utils";
import "../scss/_base.scss";

const errorMessage = "There was an error fetching the theme.";

const Error = () => <p>{errorMessage}</p>;
const styleDecorator = storyFn => {
    const style = {
        padding: 24,
    };
    return (
        <>
            <Provider store={getStore()}>
                <ThemeProvider errorComponent={<Error />} themeKey="theme-variables-dark">
                    <div style={style}>
                        <Backgrounds />
                        {storyFn()}
                    </div>
                </ThemeProvider>
            </Provider>
        </>
    );
};

addDecorator(styleDecorator);
