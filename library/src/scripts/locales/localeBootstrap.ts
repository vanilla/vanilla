/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { fetchLocalesFromApi } from "@library/locales/localeActions";
import { setCurrentLocale, loadLocales, loadTranslations } from "@vanilla/i18n";
import { getMeta } from "@library/utility/appUtils";
import gdn from "@library/gdn";

export async function bootstrapLocales() {
    // Fetch the current locale from meta.
    const currentLocaleValue = getMeta("ui.localeKey", getMeta("ui.locale", null));
    setCurrentLocale(currentLocaleValue);

    // Register the redux reducer for locales and attempt to fetch them.
    // They may already be preloaded.
    const locales = await fetchLocalesFromApi();
    loadLocales(locales);
    loadTranslations(gdn.translations);
}
