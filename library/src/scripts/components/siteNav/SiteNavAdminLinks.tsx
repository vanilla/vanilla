/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import * as React from "react";
import { organize } from "@library/components/icons/navigationManager";
import Permission from "@library/users/Permission";
import { OrganizeCategoriesRoute } from "@knowledge/routes/pageRoutes";
import { t } from "@library/application";

interface IProps {
    className?: string;
    itemClass?: string;
    linkClass?: string;
    kbID: number;
}

/**
 * Implementation of SiteNav component
 */
export default class SiteNavAdminLinks extends React.Component<IProps> {
    public render() {
        return (
            <Permission permission="kb.manage">
                <ul className={classNames("siteNavAdminLinks", this.props.className)}>
                    <hr className="siteNavAdminLinks-divider" />
                    <h3 className="sr-only">{t("Admin Links")}</h3>
                    <li className="siteNavAdminLinks-item">
                        {organize()}
                        <OrganizeCategoriesRoute.Link
                            className="siteNavAdminLinks-link"
                            data={{ kbID: this.props.kbID }}
                        >
                            {t("Organize Categories")}
                        </OrganizeCategoriesRoute.Link>
                    </li>
                </ul>
            </Permission>
        );
    }
}
