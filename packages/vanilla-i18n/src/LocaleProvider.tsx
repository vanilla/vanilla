/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useContext, useEffect } from "react";
import { ILocale, getLocales, onLocaleChange, getCurrentLocale } from "./localeStore";
import { logWarning } from "@vanilla/utils";

const LocaleContext = React.createContext<{
    locales: ILocale[];
    currentLocale: string | null;
}>({
    locales: [],
    currentLocale: null,
});

export function LocaleProvider(props: { children?: React.ReactNode }) {
    const [locales, setLocales] = useState(getLocales());
    const [currentLocale, setCurrentLocale] = useState(getCurrentLocale());

    if (!currentLocale) {
        logWarning("No locale loaded for <LocaleProvider />");
    }

    useEffect(() => {
        setLocales(getLocales());
        setCurrentLocale(getCurrentLocale());

        onLocaleChange(() => {
            setLocales(getLocales());
            setCurrentLocale(getCurrentLocale());
        });
    }, [setLocales, setCurrentLocale]);

    return (
        <LocaleContext.Provider
            value={{
                locales,
                currentLocale,
            }}
        >
            {props.children}
        </LocaleContext.Provider>
    );
}

export function useLocaleInfo() {
    return useContext(LocaleContext);
}
