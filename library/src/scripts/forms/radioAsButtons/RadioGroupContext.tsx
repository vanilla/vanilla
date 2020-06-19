/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Optionalize } from "@library/@types/utils";
import React, { useEffect, useState } from "react";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/RadioInputAsButton";
import { radioInputAsButtonsClasses } from "@library/forms/radioAsButtons/radioInputAsButtons.styles";

export interface IRadioGroupProps {
    setData: (data: any) => void;
    groupID?: string;
    activeItem?: string | number;
    buttonActiveClass?: string;
    buttonClass?: string;
    classes?: IRadioInputAsButtonClasses;
}

const RadioGroupContext = React.createContext<IRadioGroupProps>({} as any);
export default RadioGroupContext;

interface IProps extends IRadioGroupProps {
    children: React.ReactNode;
}

export function RadioGroupProvider(props: IProps) {
    const [groupID, setGroupID] = useState(props.groupID);
    const [classes, setClasses] = useState(props.classes);

    useEffect(() => {
        if (!groupID) {
            setGroupID(uniqueIDFromPrefix("radioGroup"));
        }
        if (!classes) {
            setClasses(radioInputAsButtonsClasses());
        }
    }, []);

    return (
        <RadioGroupContext.Provider value={{ ...props, groupID, classes }}>{props.children}</RadioGroupContext.Provider>
    );
}

/**
 * HOC to inject DeviceContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withRadioGroup<T extends IRadioGroupProps = IRadioGroupProps>(
    WrappedComponent: React.ComponentType<T>,
) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithRadioGroup = (props: Optionalize<T, IRadioGroupProps>) => {
        return (
            <RadioGroupContext.Consumer>
                {context => {
                    // https://github.com/Microsoft/TypeScript/issues/28938
                    return <WrappedComponent {...context} {...(props as T)} />;
                }}
            </RadioGroupContext.Consumer>
        );
    };
    ComponentWithRadioGroup.displayName = `withRadioGroup(${displayName})`;
    return ComponentWithRadioGroup;
}
