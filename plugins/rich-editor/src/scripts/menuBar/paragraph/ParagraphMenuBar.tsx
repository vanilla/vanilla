/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import ParagraphMenuBarTab from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuBarTab";
import ParagraphMenuResetTab from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuResetTab";
import ParagraphMenuHeadingsTabContent from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuHeadingsTabContent";
import {
    blockquote,
    codeBlock,
    heading2,
    heading3,
    heading4,
    heading5,
    listOrdered,
    listUnordered,
    spoiler,
} from "@library/icons/editorIcons";
import { IFormats, RangeStatic } from "quill/core";
import { t } from "@library/utility/appUtils";
import ParagraphMenuListsTabContent from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuListsTabContent";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { colorOut, srOnly, unit } from "@library/styles/styleHelpers";
import { IMenuBarRadioButton } from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";
import ParagraphMenuSpecialBlockTabContent from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuSpecialBlockTabContent";
import { menuState } from "@rich-editor/menuBar/paragraph/formats/formatting";
import { style } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";
import TabHandler from "@library/dom/TabHandler";

interface IProps {
    className?: string;
    label: string;
    parentID: string;
    isMenuVisible: boolean;
    lastGoodSelection: RangeStatic;
    legacyMode: boolean;
    close: () => void;
    textFormats: any;
    menuActiveFormats: any;
    rovingIndex: number;
    setRovingIndex: (index: number, callback?: () => void) => void;
    menusRef: React.RefObject<HTMLDivElement>;
    panelsRef: React.RefObject<HTMLDivElement>;
    topLevelIcons: {
        headingMenuIcon: JSX.Element;
        listMenuIcon: JSX.Element;
        specialBlockMenuIcon: JSX.Element;
    };
}

interface IMenuBarContent {
    component: any;
    label: string;
    className?: string;
    toggleMenu: () => void;
    icon: JSX.Element;
    accessibleInstructions: string;
    open: boolean;
    items?: IMenuBarRadioButton[];
    indent?: () => void;
    outdent?: () => void;
    activeFormats: any;
    openMenu: () => void;
}

interface IState {
    headingMenuOpen: boolean;
    listMenuOpen: boolean;
    specialBlockMenuOpen: boolean;
}

/**
 * Implemented paragraph menu bar. Note that conceptually, it's a bar of menus, but visually it behaves like tabs.
 */
export default class ParagraphMenuBar extends React.Component<IProps, IState> {
    public state = {
        headingMenuOpen: false,
        listMenuOpen: false,
        specialBlockMenuOpen: false,
    };

    private itemCount: number;

    public render() {
        const { menuActiveFormats, textFormats } = this.props;
        const classes = richEditorClasses(this.props.legacyMode);
        const globalStyles = globalVariables();
        const iconStyle = style({
            width: unit(globalStyles.icon.sizes.default),
            height: unit(globalStyles.icon.sizes.default),
        });

        const menuContents: IMenuBarContent[] = [
            {
                component: ParagraphMenuHeadingsTabContent,
                accessibleInstructions: t("Toggle Heading Menu"),
                label: t("Headings"),
                toggleMenu: this.toggleHeadingsMenu,
                icon: this.props.topLevelIcons.headingMenuIcon,
                activeFormats: menuActiveFormats.headings,
                open: this.state.headingMenuOpen,
                openMenu: this.openHeadingsMenu,
                items: [
                    {
                        formatFunction: textFormats.h2,
                        icon: heading2(iconStyle),
                        text: t("Heading 2"),
                        checked: menuActiveFormats.headings.heading2,
                    },
                    {
                        formatFunction: textFormats.h3,
                        icon: heading3(iconStyle),
                        text: t("Heading 3"),
                        checked: menuActiveFormats.headings.heading3,
                    },
                    {
                        formatFunction: textFormats.h4,
                        icon: heading4(iconStyle),
                        text: t("Heading 4"),
                        checked: menuActiveFormats.headings.heading4,
                    },
                    {
                        formatFunction: textFormats.h5,
                        icon: heading5(iconStyle),
                        text: t("Heading 5"),
                        checked: menuActiveFormats.headings.heading5,
                    },
                ],
            },
            {
                component: ParagraphMenuListsTabContent,
                accessibleInstructions: t("Toggle Lists Menu"),
                label: t("Lists"),
                toggleMenu: this.toggleListsMenu,
                icon: this.props.topLevelIcons.listMenuIcon,
                activeFormats: menuActiveFormats.lists,
                open: this.state.listMenuOpen,
                openMenu: this.openListsMenu,
                items: [
                    {
                        formatFunction: textFormats.listUnordered,
                        icon: listUnordered(iconStyle),
                        text: t("Bulleted List"),
                        checked: menuActiveFormats.lists.ordered,
                    },
                    {
                        formatFunction: textFormats.listUnordered,
                        icon: listOrdered(iconStyle),
                        text: t("Ordered List"),
                        checked: menuActiveFormats.lists.unordered,
                    },
                ],
                indent: textFormats.listIndent,
                outdent: textFormats.listIndent,
            },
            {
                component: ParagraphMenuSpecialBlockTabContent,
                accessibleInstructions: t("Toggle Special Formats Menu"),
                label: t("Special Formats"),
                toggleMenu: this.toggleSpecialBlockMenu,
                icon: this.props.topLevelIcons.specialBlockMenuIcon,
                activeFormats: menuActiveFormats.specialFormats,
                open: this.state.specialBlockMenuOpen,
                openMenu: this.openSpecialBlockMenu,
                items: [
                    {
                        formatFunction: textFormats.blockquote,
                        icon: blockquote(iconStyle),
                        text: t("Quote"),
                        checked: menuActiveFormats.specialFormats.blockQuote,
                    },
                    {
                        formatFunction: textFormats.codeBlock,
                        icon: codeBlock(iconStyle),
                        text: t("Code Block"),
                        checked: menuActiveFormats.specialFormats.codeBlock,
                    },
                    {
                        formatFunction: textFormats.spoiler,
                        icon: spoiler(iconStyle),
                        text: t("Spoiler"),
                        checked: menuActiveFormats.specialFormats.spoiler,
                    },
                ],
            },
        ];

        this.itemCount = menuContents.length + 1;

        const panelContent: JSX.Element[] = [];

        const menus = menuContents.map((menu, index) => {
            const MyContent = menu.component;
            const myRovingIndex = () => {
                this.props.setRovingIndex(index);
            };

            panelContent[index] = (
                <div
                    id={MyContent.get}
                    role="menu"
                    className={!menu.open ? style(srOnly()) : undefined}
                    aria-hidden={!menu.open}
                    key={`menuBarPanel-${index}`}
                >
                    <MyContent
                        {...menu}
                        setRovingIndex={myRovingIndex}
                        disabled={!menu.open}
                        closeMenu={this.props.close}
                        closeMenuAndSetCursor={this.closeMenuAndSetCursor}
                    />
                </div>
            );

            const setMyParagraph = () => {
                this.props.setRovingIndex(index);
            };

            return (
                <ParagraphMenuBarTab
                    accessibleButtonLabel={"Toggle Heading Menu"}
                    className={menu.className}
                    index={index}
                    parentID={this.props.parentID}
                    isMenuVisible={this.props.isMenuVisible}
                    toggleMenu={menu.toggleMenu}
                    icon={menu.icon}
                    tabComponent={menu.component}
                    key={`${menu.label}-${index}`}
                    activeFormats={menu.activeFormats}
                    setRovingIndex={setMyParagraph}
                    legacyMode={this.props.legacyMode}
                    tabIndex={this.tabIndex(index)}
                    open={menu.open}
                />
            );
        });

        const paragraphIndex = menuContents.length;
        const setParagraphIndex = () => {
            this.props.setRovingIndex(0);
        };
        return (
            <div onKeyDownCapture={this.handleMenuBarKeyDown}>
                <div
                    role="menubar"
                    aria-label={this.props.label}
                    className={classNames(classes.menuBar, this.props.className)}
                    ref={this.props.menusRef}
                >
                    <div className={classes.menuBarToggles}>
                        {menus}
                        <ParagraphMenuResetTab
                            formatParagraphHandler={textFormats.paragraph}
                            setRovingIndex={setParagraphIndex}
                            tabIndex={this.tabIndex(paragraphIndex)}
                            closeMenuAndSetCursor={this.closeMenuAndSetCursor}
                            isMenuVisible={this.props.isMenuVisible}
                        />
                    </div>
                </div>
                <div ref={this.props.panelsRef}>{panelContent}</div>
            </div>
        );
    }

    public componentDidUpdate(prevProps: IProps) {
        if (this.hasMenuOpen()) {
            this.selectFirstElementInOpenPanel();
        }
    }

    public hasMenuOpen = () => {
        return this.state.specialBlockMenuOpen || this.state.headingMenuOpen || this.state.listMenuOpen;
    };

    private toggleHeadingsMenu = () => {
        this.setState({
            headingMenuOpen: !this.state.headingMenuOpen,
            listMenuOpen: false,
            specialBlockMenuOpen: false,
        });
    };
    private openHeadingsMenu = () => {
        this.setState({
            headingMenuOpen: true,
            listMenuOpen: false,
            specialBlockMenuOpen: false,
        });
    };

    private toggleListsMenu = () => {
        this.setState({
            headingMenuOpen: false,
            listMenuOpen: !this.state.listMenuOpen,
            specialBlockMenuOpen: false,
        });
    };

    private openListsMenu = () => {
        this.setState({
            headingMenuOpen: false,
            listMenuOpen: true,
            specialBlockMenuOpen: false,
        });
    };

    private toggleSpecialBlockMenu = () => {
        this.setState({
            headingMenuOpen: false,
            listMenuOpen: false,
            specialBlockMenuOpen: !this.state.specialBlockMenuOpen,
        });
    };

    private openSpecialBlockMenu = () => {
        this.setState({
            headingMenuOpen: false,
            listMenuOpen: false,
            specialBlockMenuOpen: true,
        });
    };

    private closeMenuAndSetCursor = () => {
        this.closeAllSubMenus();
        this.props.close();
    };

    private closeAllSubMenus = () => {
        this.setState({
            headingMenuOpen: false,
            listMenuOpen: false,
            specialBlockMenuOpen: false,
        });
    };

    private selectFirstElementInOpenPanel = () => {
        if (this.props.panelsRef && this.props.panelsRef.current) {
            const tabHandler = new TabHandler(this.props.panelsRef.current);
            const first = tabHandler.getInitial();
            if (first) {
                first.focus();
            }
        }
    };

    private selectLastElementInOpenPanel = () => {
        if (this.props.panelsRef && this.props.panelsRef.current) {
            const tabHandler = new TabHandler(this.props.panelsRef.current);
            const last = tabHandler.getLast();
            if (last) {
                last.focus();
            }
        }
    };

    private selectCurrentTab = () => {
        if (this.props.menusRef && this.props.menusRef.current) {
            const tabHandler = new TabHandler(this.props.menusRef.current);
            const activeTab = tabHandler.getInitial(); // will always be ok, since we have a rolling index
            if (activeTab) {
                activeTab.focus();
            }
        }
    };

    private tabIndex = index => {
        return this.props.rovingIndex === index ? 0 : -1;
    };

    /**
     * From an accessibility point of view, this is a Editor Menubar. The only difference is it has a toggled visibility
     *
     * @see https://www.w3.org/TR/wai-aria-practices-1.1/examples/menubar/menubar-2/menubar-2.html
     */
    private handleMenuBarKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (`${event.key}${event.shiftKey ? "-Shift" : ""}`) {
            case "Escape":
                if (this.hasMenuOpen()) {
                    event.stopPropagation();
                    this.closeAllSubMenus();
                    this.selectCurrentTab();
                }
                break;
            // Moves focus to first item in the menubar.
            case "Home":
                if (!this.hasMenuOpen()) {
                    event.stopPropagation();
                    event.preventDefault();
                    this.props.setRovingIndex(0);
                }
                break;
            // 	Moves focus to last item in the menubar.
            case "End":
                if (!this.hasMenuOpen()) {
                    event.stopPropagation();
                    event.preventDefault();
                    this.props.setRovingIndex(this.itemCount - 1);
                }
                break;
            // Moves focus to the next item in the menubar.
            // If focus is on the last item, moves focus to the first item.
            case "ArrowRight":
                if (!this.hasMenuOpen()) {
                    event.stopPropagation();
                    event.preventDefault();
                    this.props.setRovingIndex((this.props.rovingIndex + 1) % this.itemCount);
                }
                break;
            // Moves focus to the previous item in the menubar.
            // If focus is on the first item, moves focus to the last item.
            case "ArrowLeft":
                if (!this.hasMenuOpen()) {
                    event.stopPropagation();
                    event.preventDefault();
                    this.props.setRovingIndex((this.props.rovingIndex + (this.itemCount - 1)) % this.itemCount);
                }
                break;
            // 	Opens submenu and moves focus to last item in the submenu.
            case "ArrowUp":
                if (this.hasMenuOpen()) {
                    event.stopPropagation();
                    this.closeAllSubMenus();
                    this.selectCurrentTab();
                }
                break;
            // Moves focus to next item in the menubar having a name that starts with the typed character.
            // If none of the items have a name starting with the typed character, focus does not move.
            default:
                // TODO
                break;
        }
    };
}
