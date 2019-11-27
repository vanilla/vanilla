/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import classNames from "classnames";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import TitleBarNavItem, { ITitleBarNav } from "@library/headers/mebox/pieces/TitleBarNavItem";
import Permission from "@library/features/users/Permission";
import EditorUploadButton from "@rich-editor/editor/pieces/EditorUploadButton";

export interface ITitleBarNavProps {
    className?: string;
    linkClassName?: string;
    linkContentClassName?: string;
    listClassName?: string;
    data?: ITitleBarNav[];
    children?: React.ReactNode;
    wrapper?: JSX.Element;
    excludeExtraNavItems?: boolean;
}

/**
 * Implements Navigation component for header
 */
export default class TitleBarNav extends React.Component<ITitleBarNavProps> {
    /**
     * Add some extra class.
     *
     * @param componentClass A react component class/function
     */
    public static addNavItem(componentClass: React.ComponentType) {
        TitleBarNav.extraNavItems.push(componentClass);
    }

    /**
     * Addational items to render in the navigation.
     */
    private static extraNavItems: React.ComponentType[] = [];

    public render() {
        const classes = titleBarNavClasses();
        const dataLength = this.props.data ? Object.keys(this.props.data!).length - 1 : 0;
        const content = this.props.data
            ? this.props.data.map((item, key) => {
                  const component = (
                      <TitleBarNavItem
                          {...item}
                          className={classNames(
                              key === dataLength ? classes.lastItem : false,
                              key === 0 ? classes.firstItem : false,
                          )}
                          linkContentClassName={classNames(
                              this.props.linkContentClassName,
                              key === dataLength ? classes.lastItem : false,
                          )}
                          linkClassName={this.props.linkClassName}
                          key={key}
                      />
                  );

                  if (item.permission) {
                      return (
                          <Permission key={key} permission={item.permission}>
                              {component}
                          </Permission>
                      );
                  } else {
                      return component;
                  }
              })
            : null;

        return (
            <nav className={classNames("headerNavigation", this.props.className, classes.navigation)}>
                <ul className={classNames("headerNavigation-items", this.props.listClassName, classes.items)}>
                    {this.props.children ? <React.Fragment key={"-1"}>{this.props.children}</React.Fragment> : content}
                    {this.props.excludeExtraNavItems ??
                        TitleBarNav.extraNavItems.map((ComponentClass, i) => {
                            return (
                                <React.Fragment key={i}>
                                    <ComponentClass />
                                </React.Fragment>
                            );
                        })}
                </ul>
            </nav>
        );
    }
}
