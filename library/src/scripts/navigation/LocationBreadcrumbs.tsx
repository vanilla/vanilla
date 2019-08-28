/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import * as React from "react";
import { locationPickerClasses } from "@knowledge/modules/locationPicker/locationPickerStyles";
import { breadcrumbsClasses } from "@library/navigation/breadcrumbsStyles";

interface IProps {
    locationData: ICrumb[];
    icon?: JSX.Element;
}

/**
 * Displays the current location of a location picker.
 */
export default class LocationBreadcrumbs extends React.Component<IProps> {
    /**
     * Render a string version of a breadcrumb.
     *
     * @param breadcrumbData - The category data to render.
     * @param noDataMessage - The message if no breadcrumb is given
     */
    public static renderString(breadcrumbData: ICrumb[], noDataMessage: string = t("Set Page Location")): string {
        if (!breadcrumbData || breadcrumbData.length === 0) {
            return noDataMessage;
        }

        const accessibleCrumbSeparator = `/`;
        const crumbCount = breadcrumbData.length - 1;
        let crumbTitle = t("Page Location: ") + accessibleCrumbSeparator;
        breadcrumbData.forEach((crumb, index) => {
            const lastElement = index === crumbCount;
            crumbTitle += crumb.name;
            if (!lastElement) {
                crumbTitle += accessibleCrumbSeparator;
            }
        });

        return crumbTitle;
    }

    /**
     * Render breadcrumbs as normal react components.
     */
    public render() {
        if (this.props.locationData.length === 0) {
            return t("Set Page Location");
        }
        const { locationData } = this.props;
        const accessibleCrumbSeparator = `/`;
        const crumbCount = locationData.length - 1;
        const classes = breadcrumbsClasses();
        const crumbs = locationData.map((crumb, index) => {
            const lastElement = index === crumbCount;
            const crumbSeparator = `›`;
            return (
                <React.Fragment key={`locationBreadcrumb-${index}`}>
                    <span className={classes.crumb}>{crumb.name}</span>
                    {!lastElement && (
                        <span className={classes.separator}>
                            <span aria-hidden={true} className={classes.separatorIcon}>
                                {crumbSeparator}
                            </span>
                            <span className="sr-only">{accessibleCrumbSeparator}</span>
                        </span>
                    )}
                </React.Fragment>
            );
        });

        return (
            <span className={classes.root}>
                {this.props.icon}
                {crumbs}
            </span>
        );
    }
}
