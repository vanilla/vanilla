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
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { blotCSS } from "@rich-editor/quill/components/blotStyles";

const errorMessage = "There was an error fetching the theme.";

const Error = () => <p>{errorMessage}</p>;
const styleDecorator = storyFn => {
    const classes = storyBookClasses();
    blotCSS();
    return (
        <>
            <Provider store={getStore()}>
                <ThemeProvider errorComponent={<Error />} themeKey="theme-variables-dark">
                    <div className={classes.containerOuter}>
                        <div className={classes.containerInner}>
                            <Backgrounds />
                            {storyFn()}
                        </div>
                    </div>
                </ThemeProvider>
            </Provider>
        </>
    );
};

addDecorator(styleDecorator);
