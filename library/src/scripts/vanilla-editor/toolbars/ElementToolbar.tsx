/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import FlyoutToggle from "@library/flyouts/FlyoutToggle";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { CloseCompactIcon, CloseIcon } from "@library/icons/common";
import {
    BlockquoteIcon,
    CodeBlockIcon,
    Heading2Icon,
    Heading3Icon,
    Heading4Icon,
    Heading5Icon,
    IndentIcon,
    ListOrderedIcon,
    ListUnorderedIcon,
    OutdentIcon,
    PilcrowIcon,
    SpoilerIcon,
} from "@library/icons/editorIcons";
import { MenuBar } from "@library/MenuBar/MenuBar";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { MenuBarSubMenuItem } from "@library/MenuBar/MenuBarSubMenuItem";
import { MenuBarSubMenuItemGroup } from "@library/MenuBar/MenuBarSubMenuItemGroup";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { getSelectedBlockBoundingClientRect } from "@library/vanilla-editor/queries/getSelectedBlockBoundingClientRect";
import { MyEditor, useMyPlateEditorState } from "@library/vanilla-editor/typescript";
import { vanillaEditorClasses } from "@library/vanilla-editor/VanillaEditor.classes";
import { useVanillaEditorBounds } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import { VanillaEditorFormatter } from "@library/vanilla-editor/VanillaEditorFormatter";
import { focusEditor, getSelectionText, isRangeAcrossBlocks, useEventPlateId, useHotkeys } from "@udecode/plate-common";
import { Icon } from "@vanilla/icons";
import { EMPTY_RECT } from "@vanilla/react-utils";
import React, { useMemo, useState } from "react";

interface IElementToolbarProps {
    renderAbove?: boolean;
    isVisible?: boolean;
    onClose?: () => void;
}

export function ElementToolbar(props: IElementToolbarProps) {
    const classes = vanillaEditorClasses();
    const id = useUniqueID("elementToolbar");
    const contentID = useUniqueID("elementToolbar-content");
    const [ownIsVisible, setOwnIsVisible] = useState(props.isVisible ?? false);

    const isEdgeCase = useIsElementToolbarEdgeCase();

    const classesMenuBar = menuBarClasses();

    return (
        <FlyoutToggle
            buttonProps={{
                buttonType: ButtonTypes.CUSTOM,
            }}
            name={t("Element Toolbar")}
            buttonClassName={cx(
                classesMenuBar.menuItemIconContent,
                !props.renderAbove && classesMenuBar.floatingElementMenuButton,
            )}
            buttonContents={
                ownIsVisible && !props.renderAbove ? (
                    <>
                        {/* Shrinking this down to 12 to fit */}
                        <Icon style={{ width: 12, height: 12 }} icon="dismiss-compact" />
                    </>
                ) : isEdgeCase ? (
                    <PilcrowIcon />
                ) : (
                    <TopLevelIcon />
                )
            }
            disabled={isEdgeCase}
            id={id}
            contentID={contentID}
            openAsModal={false}
            alwaysRender={true}
            renderAbove={!!props.renderAbove}
            forceVisible={props.isVisible}
            onClose={props.onClose}
            onVisibilityChange={(isVisible) => {
                setOwnIsVisible(isVisible);
                if (!isVisible) {
                    props.onClose?.();
                }
            }}
        >
            {(toggleProps) => (
                <FixedElementToolbar
                    id={contentID}
                    aria-hidden={toggleProps.isVisible ? false : true}
                    className={classes.elementToolbarContents(props.renderAbove ? "above" : "below")}
                    style={{
                        display: toggleProps.isVisible ? undefined : "none",
                    }}
                    closeHandler={props.onClose}
                />
            )}
        </FlyoutToggle>
    );
}

// FIXME: determine what behaviours we want for these edge cases
function useIsElementToolbarEdgeCase(): boolean {
    const editor: MyEditor | null | undefined = useMyPlateEditorState(useEventPlateId());
    const formatter = new VanillaEditorFormatter(editor);
    const selectionText = editor ? getSelectionText(editor) : "";

    // We don't currently have any selection in the editor, so we can't position the toolbar.
    if (!editor?.selection) {
        return true;
    }

    // We don't currently handle applying block formats across multiple lines well so bail out.
    if (selectionText.length > 0 && isRangeAcrossBlocks(editor)) {
        return false;
    }
    // Don't show it if an embed is selected
    if (formatter.isEmbed()) {
        return true;
    }

    return false;
}

/* - Floating Menu is dynamically positioned alongside the left gutter of the editor, following along with currently selected element. */
interface IFloatingElementToolbarProps extends IElementToolbarProps {
    /** If the positioning should account for a full page editor */
    fullPage?: boolean;
}
export function FloatingElementToolbar(props: IFloatingElementToolbarProps) {
    const { fullPage = false, ...restProps } = props;
    const [open, setOpen] = useState(false);

    const editor = useMyPlateEditorState(useEventPlateId());
    const { boundsRef } = useVanillaEditorBounds();

    const classes = vanillaEditorClasses();
    const selectionRect = getSelectedBlockBoundingClientRect(editor) ?? EMPTY_RECT;

    const isEdgeCase = useIsElementToolbarEdgeCase();

    // We need to recalculate this on each new selection. useMeasure will only update on resize.
    const editorRect = useMemo(() => {
        return boundsRef?.current?.getBoundingClientRect() ?? EMPTY_RECT;
    }, [selectionRect]);

    const toolbarPosition = useMemo(() => {
        // Magic number 34: The height of the floating toolbar button
        const yValue = selectionRect.top - editorRect.top + (fullPage ? -34 : 0) + selectionRect.height / 2;
        return `translate(calc(-100% + -4px), calc(${yValue}px - 50%))`;
    }, [selectionRect, editorRect, fullPage]);

    useHotkeys(
        "ctrl+shift+p",
        (e) => {
            setOpen(true);
        },
        {
            enabled: !isEdgeCase || editorRect === EMPTY_RECT || selectionRect === EMPTY_RECT,
            enableOnContentEditable: true,
        },
        [],
    );

    // If this is one of the edge cases, don't render the floating toolbar.
    if (isEdgeCase) {
        return <></>;
    }

    // Sometimes we don't have anything to measure. In these cases we should not be rendering the menu
    // Or it may be floating off the screen somewhere.
    if (editorRect === EMPTY_RECT || selectionRect === EMPTY_RECT) {
        return <></>;
    }

    return (
        <span className={classes.elementToolbarPosition} style={{ transform: toolbarPosition }}>
            <ElementToolbar
                {...restProps}
                isVisible={open}
                onClose={() => {
                    setOpen(false);
                }}
            />
        </span>
    );
}

function TopLevelIcon() {
    const { headingIcon, listIcon, otherIcon } = useTabIcons();
    return headingIcon ?? listIcon ?? otherIcon ?? <PilcrowIcon />;
}

function useTabIcons() {
    const editor = useMyPlateEditorState(useEventPlateId());
    const formatter = new VanillaEditorFormatter(editor);
    const headingIcon = (() => {
        if (formatter.isH2()) {
            return <Heading2Icon />;
        } else if (formatter.isH3()) {
            return <Heading3Icon />;
        } else if (formatter.isH4()) {
            return <Heading4Icon />;
        } else if (formatter.isH5()) {
            return <Heading5Icon />;
        } else {
            return null;
        }
    })();

    const listIcon = (() => {
        if (formatter.isOrderedList()) {
            return <ListOrderedIcon />;
        } else if (formatter.isUnorderedList()) {
            return <ListUnorderedIcon />;
        } else {
            return null;
        }
    })();

    const otherIcon = (() => {
        if (formatter.isBlockquote()) {
            return <BlockquoteIcon />;
        } else if (formatter.isCodeBlock()) {
            return <CodeBlockIcon />;
        } else if (formatter.isSpoiler()) {
            return <SpoilerIcon />;
        } else {
            return null;
        }
    })();

    return { headingIcon, listIcon, otherIcon };
}

/**
 * Toolbar for controlling element/block level formats in the editor.
 * - Many items in this menu are exclusive of each other.
 */

function FixedElementToolbar(props: React.HTMLAttributes<HTMLDivElement> & { closeHandler?: () => void }) {
    const { closeHandler, ...restProps } = props;
    const editor = useMyPlateEditorState(useEventPlateId());
    const formatter = new VanillaEditorFormatter(editor);

    const { headingIcon, listIcon, otherIcon } = useTabIcons();

    const handleOnActivate = (format: keyof VanillaEditorFormatter) => {
        formatter[format]();
        closeHandler?.();
    };

    return (
        <MenuBar {...restProps}>
            <MenuBarItem
                accessibleLabel={t("Toggle Heading Menu")}
                active={headingIcon !== null}
                icon={headingIcon ?? <Heading2Icon />}
            >
                <MenuBarSubMenuItemGroup>
                    <MenuBarSubMenuItem
                        onActivate={() => handleOnActivate("h2")}
                        active={formatter.isH2()}
                        icon={<Heading2Icon />}
                    >
                        {t("Heading 2")}
                    </MenuBarSubMenuItem>
                    <MenuBarSubMenuItem
                        onActivate={() => handleOnActivate("h3")}
                        active={formatter.isH3()}
                        icon={<Heading3Icon />}
                    >
                        {t("Heading 3")}
                    </MenuBarSubMenuItem>
                    <MenuBarSubMenuItem
                        onActivate={() => handleOnActivate("h4")}
                        active={formatter.isH4()}
                        icon={<Heading4Icon />}
                    >
                        {t("Heading 4")}
                    </MenuBarSubMenuItem>
                    <MenuBarSubMenuItem
                        onActivate={() => handleOnActivate("h5")}
                        active={formatter.isH5()}
                        icon={<Heading5Icon />}
                    >
                        {t("Heading 5")}
                    </MenuBarSubMenuItem>
                </MenuBarSubMenuItemGroup>
            </MenuBarItem>
            <MenuBarItem
                accessibleLabel={t("Toggle Lists Menu")}
                active={listIcon !== null}
                icon={listIcon ?? <ListUnorderedIcon />}
            >
                <MenuBarSubMenuItemGroup>
                    <MenuBarSubMenuItem
                        onActivate={() => handleOnActivate("unorderedList")}
                        active={formatter.isUnorderedList()}
                        icon={<ListUnorderedIcon />}
                    >
                        {t("Bulleted List")}
                    </MenuBarSubMenuItem>
                    <MenuBarSubMenuItem
                        onActivate={() => handleOnActivate("orderedList")}
                        active={formatter.isOrderedList()}
                        icon={<ListOrderedIcon />}
                    >
                        {t("Ordered List")}
                    </MenuBarSubMenuItem>
                </MenuBarSubMenuItemGroup>
                <MenuBarSubMenuItemGroup>
                    <MenuBarSubMenuItem
                        disabled={!formatter.canIndentList()}
                        onActivate={() => handleOnActivate("indentList")}
                        icon={<IndentIcon />}
                    >
                        {t("Indent")}
                    </MenuBarSubMenuItem>
                    <MenuBarSubMenuItem
                        disabled={!formatter.canOutdentList()}
                        onActivate={() => handleOnActivate("outdentList")}
                        icon={<OutdentIcon />}
                    >
                        {t("Outdent")}
                    </MenuBarSubMenuItem>
                </MenuBarSubMenuItemGroup>
            </MenuBarItem>
            <MenuBarItem
                active={otherIcon !== null}
                accessibleLabel={t("Toggle Special Formats Menu")}
                icon={otherIcon ?? <BlockquoteIcon />}
            >
                <MenuBarSubMenuItemGroup>
                    <MenuBarSubMenuItem
                        active={formatter.isBlockquote()}
                        onActivate={() => handleOnActivate("blockquote")}
                        icon={<BlockquoteIcon />}
                    >
                        {t("Quote")}
                    </MenuBarSubMenuItem>
                    <MenuBarSubMenuItem
                        active={formatter.isCodeBlock()}
                        onActivate={() => handleOnActivate("codeBlock")}
                        icon={<CodeBlockIcon />}
                    >
                        {t("Code Block")}
                    </MenuBarSubMenuItem>
                    <MenuBarSubMenuItem
                        active={formatter.isSpoiler()}
                        onActivate={() => handleOnActivate("spoiler")}
                        icon={<SpoilerIcon />}
                    >
                        {t("Spoiler")}
                    </MenuBarSubMenuItem>
                </MenuBarSubMenuItemGroup>
            </MenuBarItem>
            <MenuBarItem
                accessibleLabel={t("Paragraph (Removes paragraph style and sets to plain paragraph)")}
                icon={<PilcrowIcon />}
                onActivate={() => handleOnActivate("paragraph")}
                active={formatter.isParagraph()}
            />
        </MenuBar>
    );
}
