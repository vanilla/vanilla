/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";
import moment from "moment";
import { t } from "@library/application";
import PageHeading from "@library/components/PageHeading";
import Heading from "@library/components/Heading";
import classNames from "classnames";

interface IProps {
    title: string; // For accessibility, will not be seen by user
    maxTiles: number;
    classNames?: string;
}

/**
 * Component for displaying lists in "tiles"
 */
export default class ListsInTiles extends Component<IProps> {
    public render() {
        return (
            <section className={classNames("listsInTiles", this.props.classNames)}>
                <div className="sr-only">
                    <Heading title={this.props.title} />
                </div>
                <ul className="listsInTiles-tiles">{}</ul>
            </section>
        );
    }
}
