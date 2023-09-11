/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { t } from "@vanilla/i18n";
import { RadioGroup } from "@library/forms/radioAsButtons/RadioGroup";
import { searchInFilterClasses } from "@library/search/searchInFilter.styles";
import { buttonClasses } from "@library/forms/Button.styles";
import RadioInputAsButton from "@library/forms/radioAsButtons/RadioInputAsButton";
import isEqual from "lodash/isEqual";
import { IDateModifierRange, IDateModifierRangePickerProps } from "@library/forms/rangePicker/types";
import { dateModifier, DateModifierBuilder } from "@library/forms/rangePicker/utils";

interface IPreset {
    label: string;
    range: IDateModifierRange;
}

function makePresets(): IPreset[] {
    // Share a common date across all relative dates.
    const mod = () => dateModifier();

    const makeRange = (
        from: (mod: DateModifierBuilder) => DateModifierBuilder,
        to: (mod: DateModifierBuilder) => DateModifierBuilder = (m) => m.subtract(0, "days"),
    ): IDateModifierRange => {
        return {
            from: from(mod()).build(),
            to: to(mod()).build(),
        };
    };

    const daysFromNowLabel = (amount: number) => t("Last %s days").replace("%s", String(amount));

    return [
        { label: daysFromNowLabel(7), range: makeRange((m) => m.subtract(7, "days")) },
        { label: daysFromNowLabel(14), range: makeRange((m) => m.subtract(14, "days")) },
        { label: daysFromNowLabel(28), range: makeRange((m) => m.subtract(28, "days")) },
        { label: daysFromNowLabel(30), range: makeRange((m) => m.subtract(30, "days")) },
        { label: t("This Week"), range: makeRange((m) => m.startOf("week")) },
        { label: t("This Month"), range: makeRange((m) => m.startOf("month")) },
        {
            label: t("Last Week"),
            range: makeRange(
                (m) => m.startOf("week").subtract(1, "weeks"),
                (m) => m.endOf("week").subtract(1, "weeks"),
            ),
        },
        {
            label: t("Last Month"),
            range: makeRange(
                (m) => m.startOf("month").subtract(1, "months"),
                (m) => m.endOf("month").subtract(1, "months"),
            ),
        },
    ];
}

export function RangePresets(props: IDateModifierRangePickerProps) {
    const { range, setRange } = props;
    const customPreset = useMemo((): IPreset => ({ label: t("Custom"), range }), [range]);
    const defaultPresets = useMemo(() => makePresets(), []);
    const customPresetID = defaultPresets.length;
    const selectedPresetID = useMemo(
        () => defaultPresets.findIndex((preset) => isEqual(preset.range, range)),
        [defaultPresets, range],
    );
    const presets = useMemo(
        () => (selectedPresetID >= 0 ? defaultPresets : [...defaultPresets, customPreset]),
        [selectedPresetID, defaultPresets, customPreset],
    );
    const selectedID = selectedPresetID >= 0 ? selectedPresetID : customPresetID;

    return (
        <RadioGroup
            classes={searchInFilterClasses()}
            buttonClass={buttonClasses().radio}
            buttonActiveClass={buttonClasses().radio}
            activeItem={selectedID}
            setData={(recordID) => {
                if (!setRange) return;
                setRange(presets[recordID].range);
            }}
        >
            {presets.map(({ label }, recordID) => (
                <RadioInputAsButton key={label} label={label} data={recordID} />
            ))}
        </RadioGroup>
    );
}
