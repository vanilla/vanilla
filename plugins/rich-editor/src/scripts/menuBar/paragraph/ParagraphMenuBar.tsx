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
import Formatter from "@rich-editor/quill/Formatter";
import { IFormats, RangeStatic } from "quill/core";
import { getActiveFormats, paragraphFormats } from "@rich-editor/menuBar/paragraph/formats/formatting";
import { t } from "@library/utility/appUtils";
import ParagraphMenuListsTabContent from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuListsTabContent";
import ParagraphMenuBlockTabContent from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuSpecialBlockTabContent";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { srOnly } from "@library/styles/styleHelpers";
import { IMenuBarRadioButton } from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";

interface IProps {
    className?: string;
    label: string;
    parentID: string;
    isMenuVisible: boolean;
    formatter: Formatter;
    lastGoodSelection: RangeStatic;
    activeFormats: IFormats;
    legacyMode: boolean;
    close: () => void;
}

interface IState {
    rovingIndex: number;
    headingMenuIcon: JSX.Element;
    headingMenuOpen: boolean;
    listMenuIcon: JSX.Element;
    listMenuOpen: boolean;
    specialBlockMenuIcon: JSX.Element;
    specialBlockMenuOpen: boolean;
}

interface IMenuBarContent {
    component: any;
    label: string;
    className?: string;
    toggleMenu: () => void;
    icon: JSX.Element;
    accessibleInstructions: string;
    activeFormats: {} | boolean;
    open: boolean;
    items?: IMenuBarRadioButton[];
    indent?: () => void;
    outdent?: () => void;
}

/**
 * Implemented generic tab component.
 */
export default class ParagraphMenuBar extends React.Component<IProps, IState> {
    private menuCount;
    public state = {
        rovingIndex: 0,
        headingMenuIcon: heading2(),
        headingMenuOpen: false,
        listMenuIcon: listUnordered(),
        listMenuOpen: false,
        specialBlockMenuIcon: blockquote(),
        specialBlockMenuOpen: false,
    };

    private setTopLevelIcons() {
        const menuActiveFormats = getActiveFormats(this.props.activeFormats);

        let headingIcon = heading2();
        for (const key in menuActiveFormats.headings) {
            if (menuActiveFormats[key] === true) {
                switch (key) {
                    case "heading3":
                        headingIcon = heading3();
                        break;
                    case "heading4":
                        headingIcon = heading4();
                        break;
                    case "heading5":
                        headingIcon = heading5();
                        break;
                }
            }
        }

        let specialIcon = blockquote();
        for (const key in menuActiveFormats.specialFormats) {
            if (menuActiveFormats[key] === true) {
                switch (key) {
                    case "codeBlock":
                        specialIcon = codeBlock();
                        break;
                    case "spoiler":
                        specialIcon = spoiler();
                        break;
                }
            }
        }

        let listIcon = listUnordered();
        for (const key in menuActiveFormats.lists) {
            if (menuActiveFormats[key] === true) {
                // TODO update to list
                if (key === "ordered") {
                    listIcon = listOrdered();
                }
            }
        }

        this.setState({
            headingMenuIcon: headingIcon,
            listMenuIcon: listIcon,
            specialBlockMenuIcon: specialIcon,
        });
    }

    public componentDidMount() {
        this.setTopLevelIcons();
    }

    public render() {
        const { activeFormats } = this.props;
        let isParagraphEnabled = true;
        ["header", BlockquoteLineBlot.blotName, CodeBlockBlot.blotName, SpoilerLineBlot.blotName].forEach(item => {
            if (item && item in activeFormats) {
                isParagraphEnabled = false;
            }
        });
        const classes = richEditorClasses(this.props.legacyMode);
        const formats = paragraphFormats(this.props.formatter, this.props.lastGoodSelection);

        const formatsActive = getActiveFormats(activeFormats);

        const menuContents: IMenuBarContent[] = [
            {
                component: ParagraphMenuHeadingsTabContent,
                accessibleInstructions: t("Toggle Heading Menu"),
                label: t("Headings"),
                toggleMenu: this.toggleHeadingsMenu,
                icon: this.state.headingMenuIcon,
                activeFormats: formatsActive.headings,
                open: this.state.headingMenuOpen,
                items: [
                    {
                        formatFunction: formats.formatH2,
                        icon: heading2(),
                        text: t("Heading 2"),
                        checked: formatsActive.headings.heading2,
                    },
                    {
                        formatFunction: formats.formatH3,
                        icon: heading3(),
                        text: t("Heading 3"),
                        checked: formatsActive.headings.heading3,
                    },
                    {
                        formatFunction: formats.formatH4,
                        icon: heading4(),
                        text: t("Heading 4"),
                        checked: formatsActive.headings.heading4,
                    },
                    {
                        formatFunction: formats.formatH5,
                        icon: heading5(),
                        text: t("Heading 5"),
                        checked: formatsActive.headings.heading5,
                    },
                ],
            },
            {
                component: ParagraphMenuListsTabContent,
                accessibleInstructions: t("Toggle Lists Menu"),
                label: t("Lists"),
                toggleMenu: this.toggleListsMenu,
                icon: this.state.listMenuIcon,
                activeFormats: formatsActive.lists,
                open: this.state.listMenuOpen,
                items: [
                    {
                        formatFunction: formats.listUnordered,
                        icon: listUnordered(),
                        text: t("Bulleted List"),
                        checked: formatsActive.lists.ordered,
                    },
                    {
                        formatFunction: formats.listUnordered,
                        icon: listOrdered(),
                        text: t("Ordered List"),
                        checked: formatsActive.lists.unordered,
                    },
                ],
                indent: formats.listIndent,
                outdent: formats.listIndent,
            },
            {
                component: ParagraphMenuBlockTabContent,
                accessibleInstructions: t("Toggle Special Formats Menu"),
                label: t("Special Formats"),
                toggleMenu: this.toggleSpecialBlockMenu,
                icon: this.state.specialBlockMenuIcon,
                activeFormats: formatsActive.specialFormats,
                open: this.state.specialBlockMenuOpen,
            },
        ];

        const panelContent: JSX.Element[] = [];

        const menus = menuContents.map((menu, index) => {
            const setMyIndex = () => {
                this.setRovingIndex(index);
            };
            const MyContent = menu.component;

            panelContent.push(
                <div
                    id={MyContent.get}
                    role="menu"
                    key={`menuBarItem-${index}`}
                    style={!menu.open ? srOnly() : undefined}
                >
                    {menu.open && <MyContent {...menu} closeMenuAndSetCursor={this.closeMenuAndSetCursor} />}
                </div>,
            );
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
                    setRovingIndex={setMyIndex}
                    key={`${menu.label}-${index}`}
                    activeFormats={menu.activeFormats}
                    legacyMode={this.props.legacyMode}
                    tabIndex={this.tabIndex(index)}
                    open={menu.open}
                />
            );
        });

        const paragraphIndex = menuContents.length + 1;
        const setParagraphIndex = () => {
            this.setRovingIndex(paragraphIndex);
        };
        this.menuCount = paragraphIndex + 1;
        return (
            <>
                <div
                    role="menubar"
                    aria-label={this.props.label}
                    className={classNames(classes.menuBar, this.props.className)}
                >
                    <div className={classes.menuBarToggles}>
                        {menus}
                        <ParagraphMenuResetTab
                            isActive={formatsActive.paragraph}
                            formatParagraphHandler={formats.formatParagraph}
                            setRovingIndex={setParagraphIndex}
                            tabIndex={this.tabIndex(paragraphIndex)}
                            closeAllSubMenus={this.closeAllSubMenus}
                        />
                    </div>
                </div>
                {panelContent}
            </>
        );
    }

    private toggleHeadingsMenu = () => {
        this.setState({
            headingMenuOpen: !this.state.headingMenuOpen,
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

    private toggleSpecialBlockMenu = () => {
        this.setState({
            headingMenuOpen: false,
            listMenuOpen: false,
            specialBlockMenuOpen: !this.state.specialBlockMenuOpen,
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

    private tabIndex = index => {
        if (this.state.rovingIndex === null && index === 0) {
            return 0;
        } else {
            return this.state.rovingIndex === index ? 0 : -1;
        }
    };

    /**
     * For the "roving tab index" (https://www.w3.org/TR/wai-aria-practices-1.1/#kbd_roving_tabindex), we need to know the count. It'll be initialized when by the ParagraphMenuBar
     * @param count
     */
    private setRovingIndex(index: number) {
        this.setState(
            {
                rovingIndex: index,
            },
            () => {
                this.forceUpdate();
            },
        );
    }

    /**
     * From an accessibility point of view, this is a Editor Menubar. The only difference is it has a toggled visibility
     *
     * @see https://www.w3.org/TR/wai-aria-practices-1.1/examples/menubar/menubar-2/menubar-2.html
     */
    private handleMenuBarKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (`${event.key}${event.shiftKey ? "-Shift" : ""}`) {
            // Opens submenu and moves focus to first item in the submenu.
            case "Space":
            case "Enter":
                break;
            // Moves focus to first item in the menubar.
            case "Home":
                break;
            // 	Moves focus to last item in the menubar.
            case "End":
                break;
            // Moves focus to the next item in the menubar.
            // If focus is on the last item, moves focus to the first item.
            case "ArrowRight":
                break;
            // Moves focus to the previous item in the menubar.
            // If focus is on the first item, moves focus to the last item.
            case "ArrowLeft":
                break;
            // 	Opens submenu and moves focus to last item in the submenu.
            case "ArrowUp":
                // event.preventDefault();
                // this.setState({ hasFocus: true }, () => {
                //     // this.menuRef.current!.focusFirstItem();
                // });
                break;
            // Opens submenu and moves focus to first item in the submenu.
            case "ArrowDown":
                event.preventDefault();
                // this.setState({ hasFocus: true }, () => {
                //     // this.menuRef.current!.focusLastItem();
                // });
                break;
            case "Tab": // handle tab because of roving index
                event.preventDefault();
                // this.setState({ hasFocus: true }, () => {
                //     // this.menuRef.current!.focusLastItem();
                // });
                break;
            case "Tab-Shift": // handle shift+tab because of roving index
                event.preventDefault();
                // this.setState({ hasFocus: true }, () => {
                //     // this.menuRef.current!.focusLastItem();
                // });
                break;
            // Moves focus to next item in the menubar having a name that starts with the typed character.
            // If none of the items have a name starting with the typed character, focus does not move.
            default:
                break;
        }
    };
}
