/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { uniqueIDFromPrefix } from "@library/componentIDs";
import { classNames as className } from "react-select/lib/utils";
import { ISearchDomain } from "@knowledge/modules/search/components/AdvancedSearch";
import RadioButtonTab from "./RadioButtonTab";
import { ReactNode } from "react";
import { t } from "@library/application";

interface IProps {
    prefix: string;
    accessibleTitle: string; // Describe what these buttons represent. Hidden from view, for screen readers
    // children: ReactNode; // Remove
    selectedTab: ISearchDomain;
    className?: string;
    setData: (data: any) => void;
}

/**
 * Implement what looks like tabs, but what is semantically radio buttons.
 */
export default class RadioButtonsAsTabs extends React.Component<IProps> {
    private groupID;

    public constructor(props) {
        super(props);
        this.groupID = uniqueIDFromPrefix(this.props.prefix);
    }

    public render() {
        // const content = this.props.children.map((child, index) => {
        //     return child.render(this.groupID, this.props.setData, this.props.selectedTab);
        // });

        return (
            <fieldset
                className={className("inputBlock radioButtonsAsTabs _searchBarAdvanced-searchIn", this.props.className)}
            >
                <legend className="sr-only">{this.props.accessibleTitle}</legend>
                <div className="radioButtonsAsTabs-tabs">
                    <RadioButtonTab
                        groupID={this.groupID}
                        label={t("Articles")}
                        setData={this.props.setData}
                        data={ISearchDomain.ARTICLES}
                    />
                    <RadioButtonTab
                        groupID={this.groupID}
                        label={t("Everywhere")}
                        setData={this.props.setData}
                        data={ISearchDomain.ARTICLES}
                    />
                </div>
            </fieldset>
        );
    }
}
