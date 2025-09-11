/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { IContentTranslatorProps, NullContentTranslator } from "./ContentTranslator";
import { useLocaleInfo } from "./LocaleProvider";

interface IContentTranslator {
    shouldDisplay: boolean;
    Translator: React.ComponentType<IContentTranslatorProps>;
}

const context = React.createContext<IContentTranslator>({
    shouldDisplay: false,
    Translator: NullContentTranslator,
});

let _TranslationComponent: React.ComponentType<IContentTranslatorProps> | null = null;

/**
 * Provider of the translation component.
 */
export const ContentTranslationProvider = (props: { children: React.ReactNode }) => {
    const { currentLocale, locales } = useLocaleInfo();

    return (
        <context.Provider
            value={{
                shouldDisplay: !!_TranslationComponent && locales.some((locale) => locale.localeKey !== currentLocale),
                Translator: _TranslationComponent !== null ? _TranslationComponent : NullContentTranslator,
            }}
        >
            {props.children}
        </context.Provider>
    );
};

ContentTranslationProvider.setTranslator = (Translator: React.ComponentType<IContentTranslatorProps>) => {
    _TranslationComponent = Translator;
};

export function useContentTranslator(): IContentTranslator {
    return useContext(context);
}
