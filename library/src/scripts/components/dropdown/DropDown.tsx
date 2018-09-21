/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import PopoverController, {
    IDropDownControllerChildParameters,
} from "@rich-editor/components/popovers/pieces/PopoverController";
import { dropDownMenu } from "@library/components/Icons";
import { getRequiredID } from "@library/componentIDs";

export interface IProps {
    name: string;
    children: React.ReactNode;
    className?: string;
    isList?: boolean;
}

export interface IState {
    id: string;
}

export default class DropDownToggleButton extends React.PureComponent<IProps, IState> {
    public defaultProps = {
        isList: true,
    };

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "dropDown"),
        };
    }

    public render() {
        return (
            <PopoverController id={this.state.id} classNameRoot="dropDown" icon={dropDownMenu()}>
                {(params: IDropDownControllerChildParameters) => {
                    if (this.props.isList) {
                        return <ul className="dropDownItems">{this.props.children}</ul>;
                    } else {
                        return <React.Fragment>{this.props.children}</React.Fragment>;
                    }
                }}
            </PopoverController>
        );
    }
}
