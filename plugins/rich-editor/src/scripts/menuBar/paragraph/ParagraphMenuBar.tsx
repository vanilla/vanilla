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
import { menuState, IParagraphFormatter, IParagraphMenuState } from "@rich-editor/menuBar/paragraph/formats/formatting";
import { style } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";

interface IProps {
    className?: string;
    label: string;
    parentID: string;
    isMenuVisible: boolean;
    lastGoodSelection: RangeStatic;
    legacyMode: boolean;
    close: () => void;
    textFormats: IParagraphFormatter;
    menuActiveFormats: IParagraphMenuState;
    rovingIndex: number;
    setRovingIndex: (index: number, callback?: () => void) => void;
    menuRef: React.RefObject<HTMLDivElement>;
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
}

/**
 * Implemented paragraph menu bar. Note that conceptually, it's a bar of menus, but visually it behaves like tabs.
 */
export default class ParagraphMenuBar extends React.Component<IProps> {
    private headingMenuOpen = false;
    private headingMenuIcon = heading2();
    private listMenuOpen = false;
    private listMenuIcon = listUnordered();
    private specialBlockMenuOpen = false;
    private specialBlockMenuIcon = blockquote();

    private topLevelIcons() {
        const { menuActiveFormats } = this.props;

        let headingIcon = heading2();
        if (menuActiveFormats.headings.heading2) {
            headingIcon = heading2();
        } else if (menuActiveFormats.headings.heading3) {
            headingIcon = heading3();
        } else if (menuActiveFormats.headings.heading4) {
            headingIcon = heading4();
        } else if (menuActiveFormats.headings.heading5) {
            headingIcon = heading5();
        }

        let specialIcon = blockquote();
        if (menuActiveFormats.specialFormats.blockQuote) {
            specialIcon = blockquote();
        } else if (menuActiveFormats.specialFormats.codeBlock) {
            specialIcon = codeBlock();
        } else if (menuActiveFormats.specialFormats.spoiler) {
            specialIcon = spoiler();
        }

        let listIcon = listUnordered();
        if (menuActiveFormats.lists.ordered) {
            listIcon = listOrdered();
        } else if (menuActiveFormats.lists.unordered) {
            listIcon = listUnordered();
        }

        this.headingMenuIcon = headingIcon;
        this.specialBlockMenuIcon = specialIcon;
        this.listMenuIcon = listIcon;
    }

    public render() {
        const { menuActiveFormats, textFormats } = this.props;
        const classes = richEditorClasses(this.props.legacyMode);
        this.topLevelIcons();
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
                icon: this.headingMenuIcon,
                activeFormats: menuActiveFormats.headings,
                open: this.headingMenuOpen,
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
                icon: this.listMenuIcon,
                activeFormats: menuActiveFormats.lists,
                open: this.listMenuOpen,
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
                icon: this.specialBlockMenuIcon,
                activeFormats: menuActiveFormats.specialFormats,
                open: this.specialBlockMenuOpen,
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
                    key={`menuBarPanel-${index}`}
                >
                    <MyContent
                        {...menu}
                        setRovingIndex={myRovingIndex}
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
            <>
                <div
                    role="menubar"
                    aria-label={this.props.label}
                    className={classNames(classes.menuBar, this.props.className)}
                    ref={this.props.menuRef}
                >
                    <div className={classes.menuBarToggles}>
                        {menus}
                        <ParagraphMenuResetTab
                            isActive={menuActiveFormats.paragraph}
                            formatParagraphHandler={textFormats.paragraph}
                            setRovingIndex={setParagraphIndex}
                            tabIndex={this.tabIndex(paragraphIndex)}
                            closeMenuAndSetCursor={this.closeMenuAndSetCursor}
                        />
                    </div>
                </div>
                {panelContent}
            </>
        );
    }

    private toggleHeadingsMenu = () => {
        this.headingMenuOpen = !this.headingMenuOpen;
        this.listMenuOpen = false;
        this.specialBlockMenuOpen = false;
    };

    private toggleListsMenu = () => {
        this.headingMenuOpen = false;
        this.listMenuOpen = !this.listMenuOpen;
        this.specialBlockMenuOpen = false;
    };

    private toggleSpecialBlockMenu = () => {
        this.headingMenuOpen = false;
        this.listMenuOpen = false;
        this.specialBlockMenuOpen = !this.specialBlockMenuOpen;
    };

    private closeMenuAndSetCursor = () => {
        this.closeAllSubMenus();
        this.props.close();
    };

    private closeAllSubMenus = () => {
        this.headingMenuOpen = false;
        this.listMenuOpen = false;
        this.specialBlockMenuOpen = false;
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
