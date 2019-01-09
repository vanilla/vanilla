/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { IMeBoxProps } from "../MeBox";

interface IState {}

/**
 * Implements compact me box menu
 */
export default class CompactMenu extends React.Component<IMeBoxProps> {
    public render() {
        return <div className={classNames(this.props.className)}>{t("I'm compact!")}</div>;
    }
}
