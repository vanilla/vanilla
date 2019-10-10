/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React from "react";
import { useLocaleInfo } from "./LocaleProvider";
import { translate } from "./translationStore";
import { ILocale } from "./localeStore";

interface IProps {
    localeContent: string; // The locale key to translate into a full name.
    displayLocale?: string; // The language to use for the display.
}

/**
 * Component for displaying a locale translated into a different locale.
 *
 * Currently this relies on the subcommuntiies endpoint to provide all translations for active locale.s
 */
export function LocaleDisplayer(props: IProps) {
    const { locales, currentLocale } = useLocaleInfo();
    if (!currentLocale) {
        return null;
    }

    let selectedLocale: ILocale | null = null;
    for (const locale of locales) {
        if (locale.localeKey === props.localeContent) {
            selectedLocale = locale;
        }
    }

    if (!selectedLocale) {
        return <span lang={props.displayLocale}>Unknown Language {props.localeContent}</span>;
    }

    let fullLocaleName = selectedLocale.displayNames[props.displayLocale || currentLocale];
    if (!fullLocaleName) {
        fullLocaleName = translate(props.localeContent);
    }

    return <span lang={props.displayLocale}>{fullLocaleName}</span>;
}
