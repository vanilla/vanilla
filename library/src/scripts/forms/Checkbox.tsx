/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IOptionalComponentID } from "@library/utility/idUtils";

interface IProps extends IOptionalComponentID {
    id?: string;
    className?: string;
    checked?: boolean;
    disabled?: boolean;
    onChange?: any;
    label: string;
}

interface IState {
    id: string;
}

/**
 * Implements check box component
 */
export class CheckBox extends React.Component<IProps> {
    // public render() {
    //
    //
    //
    // get labelID(): string {
    //     return this.state.id + "-label";
    // }
    //
    // public render() {
    //     const classes = checkRadioClasses();
    //     return (
    //         <label id={this.state.id} className={classNames("checkbox", this.props.className, classes.root)}>
    //             <input
    //                 className={classNames("checkbox-input", classes.input)}
    //                 aria-labelledby={this.labelID}
    //                 type="checkbox"
    //                 onChange={this.props.onChange}
    //                 checked={this.props.checked}
    //                 disabled={this.props.disabled}
    //                 tabIndex={0}
    //             />
    //             <span className={classNames("checkbox-box", classes.iconContainer)} aria-hidden="true">
    //                 <span className={classNames("checkbox-state", classes.state)}>
    //                     <svg
    //                         className={classNames("checkbox-icon checkbox-checkIcon", classes.checkIcon)}
    //                         xmlns="http://www.w3.org/2000/svg"
    //                         viewBox="0 0 10 10"
    //                     >
    //                         <title>{t("✓")}</title>
    //                         <path
    //                             fill="currentColor"
    //                             d="M10,2.7c0-0.2-0.1-0.3-0.2-0.4L8.9,1.3c-0.2-0.2-0.6-0.2-0.9,0L3.8,5.6L1.9,3.7c-0.2-0.2-0.6-0.2-0.9,0L0.2,4.6c-0.2,0.2-0.2,0.6,0,0.9l3.2,3.2c0.2,0.2,0.6,0.2,0.9,0l5.5-5.5C9.9,3,10,2.8,10,2.7z"
    //                         />
    //                     </svg>
    //                 </span>
    //             </span>
    //             <span id={this.labelID} className={classNames("checkbox-label", classes.label)}>
    //                 {this.props.label}
    //             </span>
    //         </label>
    //     );
    // }
}
