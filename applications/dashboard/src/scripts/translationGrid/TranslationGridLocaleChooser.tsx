/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect } from "react";
import { translationGridClasses } from "./TranslationGridStyles";
import { panelListClasses } from "@library/layout/panelListStyles";
import classNames from "classnames";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useLocaleInfo, LocaleDisplayer, t } from "@vanilla/i18n";
import { useSection } from "@library/layout/LayoutContext";
import { useUniqueID } from "@library/utility/idUtils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";

interface IProps {
    /** Handler for when the locale changes. */
    onChange: (selectedLocale: string) => void;

    /** The current selected locale. */
    selectedLocale: string | null;

    /** The source locale of the translation. This will be excluded as a target from the dropdown. */
    sourceLocale: string;
}

/**
 * Component for choosing a target locale in the <TranslationGrid />
 */
export function TranslationGridLocaleChooser(props: IProps) {
    const classes = translationGridClasses();
    const classesPanelList = panelListClasses(useSection().mediaQueries);
    const { locales } = useLocaleInfo();

    const { selectedLocale, onChange } = props;
    useEffect(() => {
        if (!selectedLocale) {
            onChange(locales[0].localeKey);
        }
    }, [selectedLocale, onChange, locales]);

    const options = locales
        .filter((locale) => locale.localeKey !== props.sourceLocale)
        .map((locale): ISelectBoxItem => {
            return {
                value: locale.localeKey,
                name: locale.localeKey,
                // Commented out because we don't currently have data for it.
                // icon: (
                //     <span tabIndex={0}>
                //         <AlertIcon className={"selectBox-selectedIcon"} />
                //     </span>
                // ),
                content: (
                    <>
                        <LocaleDisplayer displayLocale={locale.localeKey} localeContent={locale.localeKey} />
                    </>
                ),
            };
        });

    const selectedOption = options.find((option) => option.value === props.selectedLocale);

    const loacalesID = useUniqueID("loacles");
    return (
        <div className={classes.languageDropdown}>
            <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                <ScreenReaderContent id={loacalesID}>{t("Locales")}</ScreenReaderContent>
                <SelectBox
                    describedBy={loacalesID}
                    value={selectedOption}
                    onChange={(option) => props.onChange(option.value)}
                    options={options}
                    buttonClassName={classes.languageDropdownToggle}
                    renderLeft={false}
                />
            </div>
        </div>
    );
}
