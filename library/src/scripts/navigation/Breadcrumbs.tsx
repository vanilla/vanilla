/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import Breadcrumb from "@library/navigation/Breadcrumb";
import { style } from "typestyle";
import classNames from "classnames";

export interface ICrumb {
    name: string;
    url: string;
}

export interface IProps {
    children: ICrumb[];
    className?: string;
    forceDisplay: boolean;
    minimumCrumbCount?: number;
}

/**
 * A component representing a string of breadcrumbs. Passa n arrow crumb props as children.
 */
export default class Breadcrumbs extends React.Component<IProps> {
    public render() {
        const minimumCrumbCount = this.props.minimumCrumbCount || 1;
        const crumbCount = this.props.children.length;
        if (crumbCount < minimumCrumbCount && !this.props.forceDisplay) {
            return null;
        }

        let content: React.ReactNode;

        content = this.props.children.map((crumb, index) => {
            const lastElement = index === crumbCount - 1;
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

        const hasForcedCrumb = crumbCount === 0 && this.props.forceDisplay;
        if (hasForcedCrumb) {
            const cssClass = style({
                minHeight: 22,
                display: "inline-block",
            });
            content = <span className={cssClass} />;
        }

        return (
            <nav
                aria-label={t("Breadcrumb")}
                className={classNames("breadcrumbs", this.props.className)}
                aria-hidden={hasForcedCrumb}
            >
                <ol className="breadcrumbs-list">{content}</ol>
            </nav>
        );
    }
}
