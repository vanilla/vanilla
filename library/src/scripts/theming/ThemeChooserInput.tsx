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

interface IProps {
    value?: number | string | null;
    initialValue: number | string;
    onChange?: (value: number | string | null) => void;
}

export function ThemeChooserInput(props: IProps) {
    const [ownValue, setOwnValue] = useState<number | string | null | undefined>(
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

    const dbThemeGroupOptions: IComboBoxOption[] = themes.map(function(theme, index) {
        return {
            value: theme.themeID,
            label: theme.name,
        };
    });

    const templateThemeGroupOptions: IComboBoxOption[] = templates.map(function(template, index) {
        return {
            value: template.themeID,
            label: template.name,
        };
    });

    const themeGroupOptions: IComboBoxOption[] = [...dbThemeGroupOptions, ...templateThemeGroupOptions];

    let selectedTheme = themeGroupOptions.find(option => {
        if (props.initialValue !== null && props.initialValue !== undefined && option.value === props.initialValue) {
            return {
                label: option.label,
                value: option.value,
            };
        }
    }) ?? { label: t("Unknown"), value: props.initialValue };

    return (
        <>
            <DashboardSelect
                options={themeGroupOptions}
                onChange={value => {
                    setValue(value ? value.value : "");
                }}
                value={selectedTheme}
            />
        </>
    );
}
