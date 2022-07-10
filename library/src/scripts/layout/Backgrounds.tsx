/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode, useContext, useEffect, useState } from "react";
import { fullBackgroundClasses, globalCSS, useBodyCSS } from "@library/layout/bodyStyles";
import { useHistory } from "react-router";

interface IProps {
    isHomePage?: boolean;
}

/**
 * Creates a drop down menu
 */
export const Backgrounds = () => {
    useBodyCSS();
    globalCSS();
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
        >
            {props.children}
        </BackgroundContext.Provider>
    );
};

const COMPAT_BG_ID = "vanillaCompatBodyBg";

export function fullBackgroundCompat(isHomePage = false) {
    if (!document.getElementById(COMPAT_BG_ID)) {
        globalCSS();

        // Make a backwards compatible body background (absolute positioned).
        const classes = fullBackgroundClasses(!!isHomePage);
        const fullBodyBackground = document.createElement("div");
        fullBodyBackground.id = COMPAT_BG_ID;
        fullBodyBackground.classList.add(classes.root);
        const frameBody = document.querySelector(".Frame-body");
        if (frameBody) {
            frameBody.prepend(fullBodyBackground);
        }
    }
}
