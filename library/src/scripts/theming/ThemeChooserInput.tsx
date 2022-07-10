/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import { t } from "@vanilla/i18n";
import React, { useEffect, useState } from "react";
import { useThemeActions } from "@library/theming/ThemeActions";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { LoadStatus } from "@library/@types/api/core";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import Translate from "@library/content/Translate";
import { RecordID } from "@vanilla/utils";

interface IProps {
    value?: RecordID | null;
    initialValue: RecordID;
    onChange?: (value: RecordID) => void;
}

/**
 * An Input that allows for users to select an available theme.
 */
export function ThemeChooserInput(props: IProps) {
    const [ownValue, setOwnValue] = useState<RecordID | null | undefined>(
        typeof props.initialValue === "boolean" ? null : props.initialValue,
    );
    const setValue = props.onChange ?? setOwnValue;

    const value = props.value ?? ownValue;
    const themeSettingsState = useThemeSettingsState();
    const actions = useThemeActions();

    useEffect(() => {
        if (themeSettingsState.themes.status === LoadStatus.PENDING) {
            actions.getAllThemes();
        }
    });

    if (!themeSettingsState.themes.data || themeSettingsState.themes.status === LoadStatus.LOADING) {
        return <DashboardSelect options={[]} value={undefined} onChange={() => {}} disabled isLoading />;
    }

    const { templates, themes } = themeSettingsState.themes.data;

    const defaultTheme = themeSettingsState.themes.data.currentTheme;

    const defaultOption: IComboBoxOption = {
        value: "",
        label: defaultTheme
            ? (((<Translate source="Default <0/>" c0={`(${defaultTheme.name})`} />) as unknown) as string)
            : t("Loading"),
    };

    const dbThemeGroupOptions: IComboBoxOption[] = themes.map(function (theme, index) {
        return {
            value: theme.themeID,
            label: theme.name,
        };
    });

    const templateThemeGroupOptions: IComboBoxOption[] = templates.map(function (template, index) {
        return {
            value: template.themeID,
            label: template.name,
        };
    });

    let themeGroupOptions: IComboBoxOption[] = [...dbThemeGroupOptions, ...templateThemeGroupOptions];
    themeGroupOptions = themeGroupOptions.filter((option) => option.value !== defaultTheme?.themeID);
    themeGroupOptions.push(defaultOption);

    const selectedTheme =
        themeGroupOptions.find((option) => {
            if (
                props.initialValue !== null &&
                props.initialValue !== undefined &&
                option.value === props.initialValue
            ) {
                return {
                    label: option.label,
                    value: option.value,
                };
            }
        }) ?? defaultOption;

    return (
        <>
            <DashboardSelect
                options={themeGroupOptions}
                onChange={(value) => {
                    setValue(value ? value.value : "");
                }}
                value={selectedTheme}
            />
        </>
    );
}
