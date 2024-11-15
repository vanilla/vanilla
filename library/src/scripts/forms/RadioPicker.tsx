/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { css } from "@emotion/css";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { FrameHeaderMinimal } from "@library/layout/frame/FrameHeaderMinimal";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { IPickerOption } from "@vanilla/json-schema-forms";
import { DropDownArrow } from "@vanilla/ui/src/forms/shared/DropDownArrow";
import { useEffect, useState } from "react";

interface IProps {
    value: string | number | boolean | undefined;
    onChange: (value: string | number | boolean) => void;
    options: IPickerOption[];
    isOpen?: boolean;
    onOpenChange?: (isOpen: boolean) => void;
    pickerTitle?: string;
}

export function RadioPicker(props: IProps) {
    const { value, options, onChange } = props;
    const [ownIsOpen, setOwnIsOpen] = useState(false);
    const isOpen = props.isOpen ?? ownIsOpen;
    const onOpenChange = props.onOpenChange ?? setOwnIsOpen;

    const currentOption = options.find((option) => option.value == value?.toString());
    const currentValue = currentOption?.value;
    const firstValue = options[0]?.value;
    useEffect(() => {
        if (currentValue === undefined && firstValue !== undefined) {
            // Set the first value.
            onChange(firstValue);
        }
    }, [currentValue, firstValue]);
    const headingID = useUniqueID("radio-picker-heading-");

    return (
        <DropDown
            isVisible={isOpen}
            onVisibilityChange={onOpenChange}
            buttonType={ButtonTypes.STANDARD}
            buttonContents={
                <span className={classes.pickerLabel}>
                    {currentOption?.label}
                    <DropDownArrow />
                </span>
            }
            flyoutType={FlyoutType.FRAME}
        >
            <Frame
                header={
                    <FrameHeaderMinimal id={headingID}>{props.pickerTitle ?? t("Choose Value")}</FrameHeaderMinimal>
                }
                body={
                    <FrameBody selfPadded={true}>
                        <div className={classes.radioGroup}>
                            <DashboardRadioGroup
                                labelType={DashboardLabelType.STANDARD}
                                labelID={headingID}
                                arrowBehaviour={"moves-focus"}
                                value={value?.toString()}
                                onChange={(newValueRaw) => {
                                    let newValue: string | number | boolean = newValueRaw;
                                    if (newValue === "true") {
                                        newValue = true;
                                    }
                                    if (newValue === "false") {
                                        newValue = false;
                                    }
                                    onChange(newValue);
                                }}
                            >
                                {options.map((option) => {
                                    return (
                                        <div key={option.label}>
                                            <DashboardRadioButton
                                                value={option.value}
                                                label={option.label}
                                                note={option.description}
                                                onChecked={() => {
                                                    onOpenChange(false);
                                                }}
                                            />
                                        </div>
                                    );
                                })}
                            </DashboardRadioGroup>
                        </div>
                    </FrameBody>
                }
            />
        </DropDown>
    );
}

const classes = {
    pickerLabel: css({
        display: "inline-flex",
        gap: 6,
        justifyContent: "space-between",
    }),
    radioGroup: css({
        padding: "6px 6px 12px",
    }),
};
