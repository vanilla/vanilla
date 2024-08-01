/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { RecordID } from "@vanilla/utils";
import React from "react";

interface IContext {
    name: string;
    value: RecordID;
    onChange: (newRecordID: RecordID) => void;
}

export const CustomRadioGroupContext = React.createContext<IContext>({
    name: "nocontext",
    value: "nocontext",
    onChange: () => {},
});

interface IProps extends IContext, Omit<React.HTMLAttributes<HTMLDivElement>, "name" | "onChange" | "value"> {}

export const CustomRadioGroup = React.forwardRef(function CustomRadioGroup(
    props: IProps,
    ref: React.RefObject<HTMLDivElement>,
) {
    const { name, value, onChange, ...htmlProps } = props;
    return (
        <CustomRadioGroupContext.Provider
            value={{
                name,
                value,
                onChange,
            }}
        >
            <div ref={ref} {...htmlProps}>
                {props.children}
            </div>
        </CustomRadioGroupContext.Provider>
    );
});
