/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { Link } from "react-router-dom";
import { downTriangle, rightTriangle } from "@library/components/Icons";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";
import TabHandler from "@library/TabHandler";

interface IProps {
    name: string;
    className?: string;
    titleID?: string;
    children: any[];
    counter: number;
    url: string;
    openParent?: () => void;
    location: any;
    depth: number;
}

interface IState {
    open: boolean;
    current: boolean;
}

/**
 * Recursive component to generate site nav item
 */
export default class SiteNavNode extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            open: false,
            current: false,
        };
    }

    /**
     * Opens node. Optional callback if it's already open.
     * @param callbackIfAlreadyOpen
     */
    public open = (callbackIfAlreadyOpen?: any) => {
        if (!this.state.open) {
            this.setState({
                open: true,
            });
        } else {
            if (callbackIfAlreadyOpen) {
                callbackIfAlreadyOpen();
            }
        }
    };

    /**
     * Closes node. Optional callback if already closed.
     * @param callbackIfAlreadyClosed
     */
    public close = (callbackIfAlreadyClosed?: any) => {
        if (this.state.open) {
            this.setState({
                open: false,
            });
        } else {
            if (callbackIfAlreadyClosed) {
                callbackIfAlreadyClosed();
            }
        }
    };

    /**
     * Triggers opening up each node up the tree if this node is the current page
     */
    public openRecursive = () => {
        if (!this.state.current && this.currentPage()) {
            this.setState({
                current: true,
            });
            if (this.props.openParent) {
                this.props.openParent();
            }
        }
    };

    /**
     * Opens self and calls same function on parent. Opens all the way to the root.
     */
    public openSelfAndOpenParent = () => {
        this.setState({
            open: true,
        });
        if (this.props.openParent) {
            this.open();
            this.props.openParent();
        }
    };

    /**
     * Toggle node
     */
    public toggle = () => {
        this.setState({
            open: !this.state.open,
        });
    };

    /**
     * Handles clicking on the chevron to toggle node
     * @param e
     */
    public handleClick = e => {
        e.preventDefault();
        this.toggle();
    };

    /**
     * Checks if we're on the current page
     * Note that this won't work with non-canonical URLs
     */
    public currentPage(): boolean {
        if (this.props.location && this.props.location.pathname) {
            return this.props.location.pathname === this.props.url;
        } else {
            return false;
        }
    }

    /**
     * Updates state with current status
     */
    public updateCurrentState() {
        this.setState({
            current: this.currentPage(),
        });
    }

    /**
     * When component updates, check if we're the current node, if so open recursively up the tree.
     * Also check if we're the current page
     * @param prevProps
     */
    public componentDidUpdate(prevProps) {
        this.openRecursive();
        if (prevProps.location.pathname !== this.props.location.pathname) {
            this.updateCurrentState();
        }
    }
    /**
     * When component gets added to DOM, check if we're the current node, if so open recursively up the tree
     * @param prevProps
     */
    public componentDidMount() {
        this.openRecursive();
    }

    public render() {
        const hasChildren = this.props.children && this.props.children.length > 0;
        const childrenContents =
            hasChildren &&
            this.props.children.map((child, i) => {
                return (
                    <SiteNavNode
                        {...child}
                        key={"siteNavNode-" + this.props.counter + "-" + i}
                        counter={this.props.counter! + 1}
                        openParent={this.openSelfAndOpenParent}
                        location={this.props.location}
                    />
                );
            });
        const space = `&nbsp;`;
        return (
            <li
                className={classNames("siteNavNode", this.props.className, { isCurrent: this.state.current })}
                role="treeitem"
                aria-expanded={this.state.open}
            >
                {hasChildren && (
                    <Button
                        tabIndex={-1}
                        ariaHidden={true}
                        title={t("Toggle Category")}
                        ariaLabel={t("Toggle Category")}
                        onClick={this.handleClick as any}
                        baseClass={ButtonBaseClass.CUSTOM}
                        className="siteNavNode-toggle"
                    >
                        {this.state.open ? downTriangle(t("Expand")) : rightTriangle(t("Collapse"))}
                    </Button>
                )}
                {!hasChildren && (
                    <span
                        className="siteNavNode-spacer"
                        aria-hidden={true}
                        dangerouslySetInnerHTML={{ __html: space }}
                    />
                )}
                <div className={classNames("siteNavNode-contents")}>
                    <Link
                        onKeyDownCapture={this.handleKeyDown}
                        className={classNames("siteNavNode-link")}
                        tabIndex={0}
                        to={this.props.url}
                    >
                        <span className="siteNavNode-label">{this.props.name}</span>
                    </Link>
                    {hasChildren && (
                        <ul className={classNames("siteNavNode-children", { isHidden: !this.state.open })} role="group">
                            {childrenContents}
                        </ul>
                    )}
                </div>
            </li>
        );
    }

    /**
     * Select next visible elemnt in tree
     * @param tabHandler The tab handler handler
     * @param currentLink The starting point
     */
    private next = (tabHandler: TabHandler, currentLink: Element) => {
        const nextElement = tabHandler.getNext(currentLink, false, false);
        if (nextElement) {
            nextElement.focus();
        }
    };

    /**
     * Select prev visible elemnt in tree
     * @param tabHandler The tab handler handler
     * @param currentLink The starting point
     */
    private prev = (tabHandler: TabHandler, currentLink: Element) => {
        const prevElement = tabHandler.getNext(currentLink, true, false);
        if (prevElement) {
            prevElement.focus();
        }
    };

    /**
     * Keyboard handler for arrow up, arrow down, home and end.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on the SiteNavNode
     * @param event
     */

    /**
     * Keyboard handler for arrow right and arrow left.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on SiteNav.tsx
     * @param event
     */
    private handleKeyDown = event => {
        const currentLink = document.activeElement;
        const siteNavRoot = currentLink.closest(".siteNav");
        const tabHandler = new TabHandler(siteNavRoot!);

        switch (event.key) {
            case "ArrowRight":
                /*
                    When focus is on a closed node, opens the node; focus does not move.
                    When focus is on a open node, moves focus to the first child node.
                    When focus is on an end node, does nothing.
                 */
                if (this.props.children && this.props.children.length > 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.open(() => {
                        this.next(tabHandler, currentLink);
                    });
                }
                break;
            case "ArrowLeft":
                /*
                    When focus is on an open node, closes the node.
                    When focus is on a child node that is also either an end node or a closed node, moves focus to its parent node.
                    When focus is on a root node that is also either an end node or a closed node, does nothing.
                */

                if (this.props.children && this.props.children.length > 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.close(() => {
                        this.prev(tabHandler, currentLink);
                    });
                } else {
                    this.prev(tabHandler, currentLink);
                }
                break;
        }
    };
}
