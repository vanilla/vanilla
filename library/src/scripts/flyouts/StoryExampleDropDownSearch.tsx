/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import KnowledgeBaseInput from "@knowledge/knowledge-bases/KnowledgeBaseInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import classNames from "classnames";

/**
 * Form component for searching/selecting a category.
 */
export default class StoryExampleDropDownSearch extends React.Component<ISelectOneProps> {
    public render() {
        return (
            <div className={"input-wrap"}>
                <SelectOne
                    className={inputBlockClasses().root}
                    onChange={() => {
                        return;
                    }}
                    value={undefined}
                    label={"DropDown with search"}
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
