/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import Button from "@library/forms/Button";
import InputTextBlock from "@library/forms/InputTextBlock";
import { durationPickerClasses } from "@library/forms/durationPicker/DurationPicker.styles";
import { DurationPickerUnit, IDurationPickerProps } from "@library/forms/durationPicker/DurationPicker.types";
import SelectOne from "@library/forms/select/SelectOne";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";

export function DurationPicker(props: IDurationPickerProps) {
    const {
        className,
        value = { length: 0, unit: DurationPickerUnit.DAYS },
        min = 0,
        max,
        lengthInputProps,
        unitInputProps,
        submitButton,
        supportedUnits,
        disabled,
        onChange,
        ...rest
    } = props;
    const { className: lengthClassName, ...lengthInputPropsRest } = lengthInputProps || {};
    const { className: unitClassName, ...unitInputPropsRest } = unitInputProps || {};
    const { className: submitClassName, tooltip, ...submitButtonRest } = submitButton || {};
    const classes = durationPickerClasses();
    const lengthInputID = uniqueIDFromPrefix("duration-length-");
    const unitInputID = uniqueIDFromPrefix("duration-unit-");

    const units = supportedUnits
        ? Object.entries(DurationPickerUnit).filter((entry) =>
              entry.some((value) => supportedUnits.includes(value as DurationPickerUnit)),
          )
        : Object.entries(DurationPickerUnit);

    const unitOptions: IComboBoxOption[] = units.map(([key, value]) => ({
        label: t(key.toLowerCase()),
        value,
    }));

    return (
        <div className={cx(classes.root, className)} {...rest}>
            <ScreenReaderContent id={lengthInputID}>{t("Duration length")}</ScreenReaderContent>
            <InputTextBlock
                inputProps={{
                    ...lengthInputPropsRest,
                    type: "number",
                    value: value?.length?.toString(),
                    onChange: (e) => onChange({ ...value, length: parseInt(e.target.value) }),
                    min,
                    max,
                    className: classes.lengthInputBox,
                    disabled: disabled,
                }}
                className={cx(classes.lengthInput, lengthClassName)}
                wrapClassName={classes.lengthInputWrap}
                labelID={lengthInputID}
            />
            <ScreenReaderContent id={unitInputID}>{t("Duration unit")}</ScreenReaderContent>
            <SelectOne
                {...unitInputPropsRest}
                options={unitOptions}
                value={unitOptions.find((o) => o.value === value?.unit)}
                defaultValue={unitOptions.find((o) => o.value === "day") || unitOptions[0]}
                onChange={(o) => onChange({ ...value, unit: o?.value as DurationPickerUnit })}
                inputClassName={cx({
                    "form-control": true,
                    [classes.unitInput]: true,
                    [classes.unitInputWithButton]: Boolean(props.submitButton),
                    ...(unitClassName ? { [unitClassName]: true } : {}),
                })}
                label={null}
                labelID={unitInputID}
                isClearable={false}
                disabled={disabled}
            />
            {submitButton && (
                <ConditionalWrap condition={Boolean(tooltip)} component={ToolTip} componentProps={{ label: tooltip }}>
                    <Button
                        {...submitButtonRest}
                        onClick={() => submitButton.onClick && submitButton.onClick(value)}
                        className={cx(classes.button, submitClassName)}
                        title={tooltip}
                        disabled={disabled}
                    />
                </ConditionalWrap>
            )}
        </div>
    );
}
