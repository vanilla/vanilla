/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import { t } from "@library/utility/appUtils";
import React from "react";

/**
 * Form component for searching/selecting a category.
 */
export default class StoryExampleDropDownSearch extends React.Component<ISelectOneProps> {
    public static defaultProps = {
        label: t("Knowledge Base"),
        options: [
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
        ],
        value: undefined,
    };

    public render() {
        return <SelectOne {...this.props} placeholder="" />;
    }
}
