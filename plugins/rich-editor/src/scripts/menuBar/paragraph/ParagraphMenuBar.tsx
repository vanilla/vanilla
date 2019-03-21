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
import ParagraphMenuBlockTabContent from "@rich-editor/menuBar/paragraph/tabs/ParagraphMenuBlockTabContent";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";

interface IProps {
    className?: string;
    label: string;
    parentID: string;
    isMenuVisible: boolean;
    formatter: Formatter;
    lastGoodSelection: RangeStatic;
    activeFormats: IFormats;
    legacyMode: boolean;
}

interface IState {
    rovingIndex: number;
    headingMenuIcon: JSX.Element;
    listMenuIcon: JSX.Element;
    blockMenuIcon: JSX.Element;
}

interface IMenuBarContent {
    component: any;
    label: string;
    className?: string;
    toggleMenu: () => void;
    icon: JSX.Element;
    accessibleInstructions: string;
    activeFormats: {} | boolean;
}

/**
 * Implemented generic tab component.
 */
export default class ParagraphMenuBar extends React.Component<IProps, IState> {
    public state = {
        rovingIndex: 0,
        headingMenuIcon: heading2(),
        listMenuIcon: listUnordered(),
        blockMenuIcon: blockquote(),
    };

    public constructor(props) {
        super(props);
        this.setTopLevelIcons();
    }

    private setTopLevelIcons() {
        const menuActiveFormats = getActiveFormats(this.props.activeFormats);
        let headingIcon;
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
                    default:
                        headingIcon = heading2();
                        break;
                }
            }
        }

        let specialIcon;
        for (const key in menuActiveFormats.lists) {
            if (menuActiveFormats[key] === true) {
                switch (key) {
                    case "blockQuote":
                        specialIcon = blockquote();
                        break;
                    case "spoiler":
                        specialIcon = spoiler();
                        break;
                    default:
                        specialIcon = codeBlock();
                        break;
                }
            }
        }

        let listIcon;
        for (const key in menuActiveFormats.lists) {
            if (menuActiveFormats[key] === true) {
                switch (key) {
                    case "ordered":
                        listIcon = listOrdered();
                        break;
                    default:
                        listIcon = listUnordered();
                        break;
                }
            }
        }

        this.setState({
            headingMenuIcon: headingIcon,
            listMenuIcon: listIcon,
            blockMenuIcon: specialIcon,
        });
    }

    public render() {
        const { activeFormats } = this.props;
        let isParagraphEnabled = true;
        ["header", BlockquoteLineBlot.blotName, CodeBlockBlot.blotName, SpoilerLineBlot.blotName].forEach(item => {
            if (item && item in activeFormats) {
                isParagraphEnabled = false;
            }
        });
        const headerObjectLevel = typeof activeFormats.header === "object" ? activeFormats.header.level : null;
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
            },
            {
                component: ParagraphMenuListsTabContent,
                accessibleInstructions: t("Toggle Lists Menu"),
                label: t("Lists"),
                toggleMenu: this.toggleListsMenu,
                icon: this.state.listMenuIcon,
                activeFormats: formatsActive.lists,
            },
            {
                component: ParagraphMenuBlockTabContent,
                accessibleInstructions: t("Toggle Special Formats Menu"),
                label: t("Special Formats"),
                toggleMenu: this.toggleBlockMenu,
                icon: this.state.blockMenuIcon,
                activeFormats: formatsActive.special,
            },
        ];

        const menus = menuContents.map((menu, index) => {
            const setMyIndex = () => {
                this.setRovingIndex(index);
            };
            const MyContent = menu.component;
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
                    rovingIndex={this.state.rovingIndex}
                    setRovingIndex={setMyIndex}
                    key={`${menu.label}-${index}`}
                    activeFormats={menu.activeFormats}
                    legacyMode={this.props.legacyMode}
                >
                    <MyContent {...menu} />
                </ParagraphMenuBarTab>
            );
        });

        return (
            <div
                role="menubar"
                aria-label={this.props.label}
                className={classNames(classes.menuBar, this.props.className)}
            >
                {menus}
                <ParagraphMenuResetTab
                    isActive={formatsActive.paragraph}
                    isDisabled={isParagraphEnabled}
                    formatParagraphHandler={formats.formatParagraph}
                    setRovingIndex={this.setRovingIndex}
                />
            </div>
        );
    }

    private toggleHeadingsMenu = () => {
        return;
    };
    private toggleListsMenu = () => {
        return;
    };
    private toggleBlockMenu = () => {
        return;
    };

    /**
     * For the "roving tab index" (https://www.w3.org/TR/wai-aria-practices-1.1/#kbd_roving_tabindex), we need to know the count. It'll be initialized when by the ParagraphMenuBar
     * @param count
     */
    private setRovingIndex(index: number) {
        this.setState({
            rovingIndex: index,
        });
    }

    /**
     * From an accessibility point of view, this is a Editor Menubar. The only difference is it has a toggled visibility
     *
     * @see https://www.w3.org/TR/wai-aria-practices-1.1/examples/menubar/menubar-2/menubar-2.html
     */
    private handleMenuBarKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
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
            // Moves focus to next item in the menubar having a name that starts with the typed character.
            // If none of the items have a name starting with the typed character, focus does not move.
            default:
                break;
        }
    };
}
