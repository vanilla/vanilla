/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne, { IMenuPlacement, MenuPlacement } from "@library/forms/select/SelectOne";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import React, {useEffect, useMemo, useState} from "react";
import { themeDropDownClasses } from "@library/forms/themeEditor/ThemeDropDown.styles";
import { ThemeType, useThemeActions } from "@library/theming/ThemeActions";
import { IThemeInfo } from "@library/theming/CurrentThemeInfo";
import { useThemeSettingsState } from "@library/theming/themeSettingsReducer";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import {DashboardSelect} from "@dashboard/forms/DashboardSelect";

interface IProps extends IMenuPlacement {
    value?: number | string | null;
    initialValue: number| string | null | boolean | undefined;
    onChange?: (value:  number| string | null | boolean | undefined) => void;
}

export function ThemeChooserInput(props: IProps) {
    const [ownValue, setOwnValue] = useState<number | string | null |undefined>(
        typeof props.initialValue === "boolean" ? null : props.initialValue
    )
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
        return <Loader />;
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


    const selectedTheme = themeGroupOptions.find(option => {
            if (option.value === props.initialValue?.toString()) {
                return {
                    label: option.label,
                    value: option.value
                }
            }

            return undefined;
        });

    return (
        <>
                <DashboardSelect
                    options={themeGroupOptions}
                    onChange={value => {
                        setValue(value ? value.value : null);
                    }}
                    value={selectedTheme}
                />
        </>
    );
}
