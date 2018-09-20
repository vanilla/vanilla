/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";



interface IProps {
    toggle?: React.ReactNode | string;
    children: React.ReactNode | Array<React.ReactNode>; // Either custom content, or array of content
}

/**
 * Generic dropdown component. Takes either a standard link or a react component.
 */
export default class DropDown extends React.Component {
    public render() {

    }
}
