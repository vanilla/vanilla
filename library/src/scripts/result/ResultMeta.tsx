/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Translate from "../content/Translate";
import DateTime from "../content/DateTime";
import { IUserFragment } from "../@types/api";
import BreadCrumbString, { ICrumbString } from "../navigation/BreadCrumbString";
import { t } from "../dom/appUtils";
import { ArticleStatus } from "../../../../plugins/knowledge/src/scripts/@types/api";
import { capitalizeFirstLetter } from "../utility/utils";
import classNames from "classnames";
import { metasClasses } from "../styles/metasStyles";

interface IProps {
    updateUser: IUserFragment;
    dateUpdated: string;
    crumbs?: ICrumbString[];
    status?: ArticleStatus;
    type?: string;
}

export class ResultMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated, updateUser, crumbs, status, type } = this.props;
        const isDeleted = status === ArticleStatus.DELETED;
        const classesMetas = metasClasses();
        return (
            <React.Fragment>
                {updateUser &&
                    updateUser.name && (
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
