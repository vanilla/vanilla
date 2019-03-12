/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { addDecorator } from "@storybook/react";
import Backgrounds from "@library/components/body/Backgrounds";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { Provider } from "react-redux";
import getStore from "@library/state/getStore";
import { ensureScript } from "@library/dom";
import "../scss/_base.scss";

const errorMessage = "There was an error fetching the theme.";

const Error = () => <p>{errorMessage}</p>;
const styleDecorator = storyFn => (
    <>
        <Provider store={getStore()}>
            <ThemeProvider errorComponent={<Error />} themeKey="keystone">
                <Backgrounds />
                {storyFn()}
            </ThemeProvider>
        </Provider>
    </>
);

addDecorator(styleDecorator);

void ensureScript("https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js").then(() => {
    window.WebFont.load({
        google: {
            families: ["Open Sans:400,400italic,600,700"], // Will be dynamic at some point
        },
    });
});
