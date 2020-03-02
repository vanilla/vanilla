/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import React from "react";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";

/**
 * Form component for searching/selecting a category.
 */
export default class StoryExampleDropDownSearch extends React.Component {
    public render() {
        return (
            <div className={"input-wrap"}>
                <SelectOne
                    className={inputBlockClasses().root}
                    onChange={() => {
                        return;
                    }}
                    value={undefined}
                    label={"Subcommunity"}
                    options={[
                        {
                            label: "Development",
                            value: 4,
                        },
                        {
                            label: "Information Security",
                            value: 7,
                        },
                        {
                            label: "Internal Testing",
                            value: 6,
                        },
                        {
                            label: "Success",
                            value: 5,
                        },
                        {
                            label: "Support",
                            value: 8,
                        },
                    ]}
                />
            </div>
        );
    }
}
