/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode, useContext, useEffect, useState } from "react";
import { fullBackgroundClasses, bodyCSS } from "@library/layout/bodyStyles";
import { useHistory } from "react-router";
import { LoadStatus } from "@library/@types/api/core";

interface IProps {
    isHomePage?: boolean;
}

/**
 * Creates a drop down menu
 */
export const Backgrounds = () => {
    bodyCSS(); // set styles on body tag
    const backgroundInfo = useBackgroundContext();
    const classes = fullBackgroundClasses(backgroundInfo.isHomePage);
    return <div className={classes.root} />;
};

const BackgroundContext = React.createContext<{ setIsHomePage: (value: boolean) => void; isHomePage: boolean }>({
    isHomePage: false,
    setIsHomePage: () => {},
});

export const useBackgroundContext = () => {
    return useContext(BackgroundContext);
};

export const BackgroundsProvider = (props: { children: ReactNode }) => {
    const [isHomePage, setIsHomePage] = useState<boolean>(false);
    const history = useHistory();
    useEffect(() => {
        const unregister = history.listen(() => {
            setIsHomePage(false);
        });
        return unregister;
    }, [history]);
    return (
        <BackgroundContext.Provider
            value={{
                isHomePage,
                setIsHomePage,
            }}
        />
    );
};

export function fullBackgroundCompat(isHomePage = false) {
    bodyCSS(); // set styles on body tag

    // Make a backwards compatible body background (absolute positioned).
    const classes = fullBackgroundClasses(!!isHomePage);
    const fullBodyBackground = document.createElement("div");
    fullBodyBackground.classList.add(classes.root);
    const frameBody = document.querySelector(".Frame-body");
    if (frameBody) {
        frameBody.prepend(fullBodyBackground);
    }
}
