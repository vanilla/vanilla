/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import className from "classnames";
import { t } from "@library/application";
import Breadcrumb from "@library/components/Breadcrumb";

export interface ICrumb {
    name: string;
    url: string;
}

export interface IProps {
    children: ICrumb[];
    className?: string;
    hideSingleCrumb?: boolean;
}

/**
 * A component representing a string of breadcrumbs. Passa n arrow crumb props as children.
 */
export default class Breadcrumbs extends React.Component<IProps> {
    public render() {
        const crumbCount = this.props.children.length - 1;
        if (this.props.hideSingleCrumb && crumbCount <= 1) {
            return null;
        }

        if (crumbCount === 0) {
            return null;
        }
        const crumbs = this.props.children.map((crumb, index) => {
            const lastElement = index === crumbCount;
            const crumbSeparator = `›`;
            return (
                <React.Fragment key={`breadcrumb-${index}`}>
                    <Breadcrumb lastElement={lastElement} name={crumb.name} url={crumb.url} />
                    {!lastElement && (
                        <li aria-hidden={true} className="breadcrumb-item breadcrumbs-separator">
                            <span className="breadcrumbs-separatorIcon">{crumbSeparator}</span>
                        </li>
                    )}
                </React.Fragment>
            );
        });
        return (
            <nav aria-label={t("Breadcrumb")} className={className("breadcrumbs", this.props.className)}>
                <ol className="breadcrumbs-list">{crumbs}</ol>
            </nav>
        );
    }
}
