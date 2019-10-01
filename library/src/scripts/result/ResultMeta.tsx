/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import { IUserFragment } from "@library/@types/api/users";
import { capitalizeFirstLetter } from "@vanilla/utils";
import { t } from "@library/utility/appUtils";
import { PublishStatus } from "@library/@types/api/core";
import BreadCrumbString, { ICrumbString } from "@library/navigation/BreadCrumbString";
import { metasClasses } from "@library/styles/metasStyles";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";

interface IProps {
    updateUser: IUserFragment;
    dateUpdated: string;
    crumbs?: ICrumbString[];
    status?: PublishStatus;
    type?: string;
}

export class ResultMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated, updateUser, crumbs, status, type } = this.props;
        const isDeleted = status === PublishStatus.DELETED;
        const classesMetas = metasClasses();
        return (
            <React.Fragment>
                {updateUser && updateUser.name && (
                    <span className={classNames(classesMetas.meta)}>
                        {isDeleted ? (
                            <span className={classNames("meta-inline", "isDeleted")}>
                                <Translate source="Deleted <0/>" c0={type} />
                            </span>
                        ) : (
                            <Translate
                                source="<0/> by <1/>"
                                c0={type ? t(capitalizeFirstLetter(type)) : undefined}
                                c1={updateUser.name}
                            />
                        )}
                    </span>
                )}

                <span className={classesMetas.meta}>
                    <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                </span>
                {crumbs && crumbs.length > 0 && <BreadCrumbString className={classesMetas.meta} crumbs={crumbs} />}
            </React.Fragment>
        );
    }
}
