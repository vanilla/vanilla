/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";
import classNames from "classnames";
import Heading from "@library/layout/Heading";
import { t } from "@library/utility/appUtils";
import { INavigationItem } from "@library/@types/api/core";
import SmartLink from "@library/routing/links/SmartLink";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { navLinksVariables, navLinksClasses } from "@library/navigation/navLinksStyles";
import Translate from "@library/content/Translate";
import { useUniqueID } from "@library/utility/idUtils";
import { RecordID } from "@vanilla/utils";
import { ArrowIcon } from "@library/icons/common";

interface IProps {
    classNames?: string;
    title: string;
    items: INavigationItem[];
    url?: string;
    recordID?: RecordID;
    recordType?: string;
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    accessibleViewAllMessage?: string;
    NoItemsComponent?: INavLinkNoItemComponent;
    hasIcon?: boolean;
}

export interface INavLinkNoItemComponentProps {
    className?: string;
    recordID?: RecordID;
    recordType?: string;
}
export type INavLinkNoItemComponent = React.ComponentType<INavLinkNoItemComponentProps>;

/**
 * Component for displaying lists in "tiles"
 */
export default function NavLinks(props: IProps) {
    const { items, NoItemsComponent } = props;

    const viewAll = t("View All");
    const classes = navLinksClasses();
    const navLinkVars = navLinksVariables();
    const contents =
        items.length > 0
            ? items.map((item, i) => {
                  return (
                      <li className={classes.item} key={i}>
                          <SmartLink to={item.url} className={classes.link} title={item.name}>
                              {item.name}
                          </SmartLink>
                      </li>
                  );
              })
            : NoItemsComponent && (
                  <li className={classes.item}>
                      <NoItemsComponent
                          className={classes.link}
                          recordID={props.recordID}
                          recordType={props.recordType}
                      />
                  </li>
              );
    const titleID = useUniqueID("navLinkTitle");
    return (
        <nav className={classNames("navLinks", props.classNames, classes.root)} aria-labelledby={titleID}>
            <Heading
                id={titleID}
                title={props.title}
                className={classNames("navLinks-title", classes.title)}
                depth={props.depth}
            />
            <ul className={classNames(classes.items)}>
                {contents}
                {props.url && props.accessibleViewAllMessage && (
                    <li className={classNames(classes.viewAllItem)}>
                        <SmartLink to={props.url} className={classNames(classes.viewAll)}>
                            <span aria-hidden={true}>{viewAll}</span>
                            {navLinkVars.viewAll.icon && <ArrowIcon />}
                            <ScreenReaderContent>
                                <Translate source={props.accessibleViewAllMessage} c0={props.title} />
                            </ScreenReaderContent>
                        </SmartLink>
                    </li>
                )}
            </ul>
        </nav>
    );
}
