import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import * as React from "react";
import { searchMiscellaneousComponentsClasses } from "@library/search/searchMiscellaneousComponents.styles";
import { useUniqueID } from "@library/utility/idUtils";
import { ILinkPages } from "@library/navigation/SimplePagerModel";

export interface IProps {
    id: string;
    label: string;
    options?: ISelectBoxItem[];
    value?: string;
    onChange?: (newValue: string) => void;
    pages?: ILinkPages;
    alignRight?: boolean;
}

export default function FilterDropDown(props: IProps) {
    const classes = searchMiscellaneousComponentsClasses();
    const id = useUniqueID(props.id);

    const valueOption =
        props.options?.find((option) => {
            return option.value === props.value;
        }) ?? undefined;

    return (
        <label className={classes.sort}>
            <span id={id} className={classes.sortLabel}>{`${props.label}: `}</span>
            <SelectBox
                options={props.options ?? []}
                value={valueOption}
                onChange={(option) => {
                    props.onChange?.(option.value);
                }}
                describedBy={id}
                widthOfParent={false}
                renderLeft={false}
                className={classes.sort}
            />
        </label>
    );
}
