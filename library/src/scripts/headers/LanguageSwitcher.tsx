/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useContext } from "react";
import { LocaleContext } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { t } from "@library/utility/appUtils";
import { getSiteSection } from "@library/utility/appUtils";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";

function replaceUrlCode({ url, oldCode, newCode }: { url: string; oldCode: string; newCode: string }) {
    if (!url || !oldCode || !newCode) {
        return;
    }

    const newURL = new URL(url);

    // Assumes url is in the form of <site basepath>/<locale code>/<subcommunity slug - optional>/<optional - specific page>
    newURL.pathname = newURL.pathname.replace(`/${oldCode}`, `/${newCode}`);

    return newURL.href;
}

export default function LanganuageSwitcher() {
    const localeContext = useContext(LocaleContext);

    const currentUrl = window.location.href;
    const currentLangCode = localeContext.currentLocale ?? getSiteSection()?.basePath?.slice(1);

    return (
        <DropDown
            name={t("Choose language")}
            flyoutType={FlyoutType.LIST}
            buttonContents={<Icon icon="me-subcommunities" />}
        >
            {localeContext.locales &&
                localeContext.locales.map((locale) => {
                    const localeLink = replaceUrlCode({
                        url: currentUrl,
                        oldCode: currentLangCode,
                        newCode: locale.localeKey,
                    });

                    if (!currentLangCode || !localeLink) {
                        return null;
                    }

                    const isCurrentLocale = locale.localeKey === currentLangCode;
                    const displayNameInOwnLocale = locale.displayNames[locale.localeKey];

                    return (
                        <DropDownItemLink
                            to={localeLink}
                            key={locale.localeKey}
                            isChecked={isCurrentLocale}
                            isBasicLink
                        >
                            {displayNameInOwnLocale}
                        </DropDownItemLink>
                    );
                })}
        </DropDown>
    );
}
