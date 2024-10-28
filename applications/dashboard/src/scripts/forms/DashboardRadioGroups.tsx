/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { useOptionalFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";
import { IRadioGroupContext, RadioGroupContext } from "@library/forms/RadioGroupContext";
import classNames from "classnames";
import React, { useRef } from "react";

interface IProps extends IRadioGroupContext {
    children: React.ReactNode;
    type?: "group" | "radiogroup";
    labelID?: string;
    arrowBehaviour?: "moves-selection" | "moves-focus";
    labelType?: DashboardLabelType;
}

export function DashboardRadioGroup(props: IProps) {
    const { labelType: _labelType } = useOptionalFormGroup();
    const type = props.type || "radiogroup";
    const selfRef = useRef<HTMLDivElement>(null);

    return (
        <RadioGroupContext.Provider value={props}>
            <DashboardInputWrap
                className={cx({ isVertical: !props.isInline && !props.isGrid })}
                isInline={props.isInline}
                isGrid={props.isGrid}
                ref={selfRef}
                onKeyDown={(e) => {
                    if (props.arrowBehaviour === "moves-focus") {
                        const allRadios: HTMLInputElement[] = Array.from(
                            selfRef.current?.querySelectorAll("input[type=radio]") ?? [],
                        );
                        const currentRadio: any = document.activeElement;

                        // moves-selection is default browser behavior.
                        // moves-selection is described as being preferable in a toolbar scenario.
                        // https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/radiogroup_role#keyboard_interactions

                        if (e.key === "ArrowDown" || e.key === "ArrowRight") {
                            e.preventDefault();

                            // Get the next radio.
                            const currentIndex = currentRadio ? allRadios.indexOf(currentRadio) : -1;
                            const nextRadio = allRadios[currentIndex + 1] ?? allRadios[0];
                            nextRadio?.focus();
                        }

                        if (e.key === "ArrowUp" || e.key === "ArrowLeft") {
                            e.preventDefault();

                            // Get the previous radio.
                            const currentIndex = currentRadio ? allRadios.indexOf(currentRadio) : 0;
                            const previousRadio = allRadios[currentIndex - 1] ?? allRadios[allRadios.length - 1];
                            previousRadio?.focus();
                        }

                        if (e.key === "Space" || e.key === "Enter") {
                            e.preventDefault();
                            currentRadio?.click();
                        }
                    }
                }}
                role={type}
                aria-labelledby={props.labelID}
            >
                {props.children}
            </DashboardInputWrap>
        </RadioGroupContext.Provider>
    );
}

export function DashboardCheckGroup(props: IProps) {
    return <DashboardRadioGroup {...props} type="group" />;
}
