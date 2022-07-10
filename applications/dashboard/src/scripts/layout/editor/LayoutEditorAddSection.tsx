/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { LayoutThumbnailsModal } from "@dashboard/layout/editor/thumbnails/LayoutThumbnailsModal";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutEditorPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Container from "@library/layout/components/Container";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useFocusOnActivate } from "@vanilla/react-utils";
import React, { useRef, useState } from "react";

interface IProps {
    path: ILayoutEditorPath;
    className?: string;
}

export const LayoutEditorAddSection = React.forwardRef(function LayoutEditorAddSection(
    props: IProps,
    ref: React.RefObject<HTMLButtonElement | null>,
) {
    const classes = layoutEditorClasses();
    const { editorContents, editorSelection, layoutViewType } = useLayoutEditor();
    const catalog = useLayoutCatalog(layoutViewType);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const ownRef = useRef<HTMLButtonElement | null>(null);
    ref = ref ?? ownRef;

    const isSelected =
        LayoutEditorPath.areSectionPathsEqual(props.path, editorSelection.getPath()) &&
        editorSelection.getMode() === LayoutEditorSelectionMode.SECTION_ADD;
    useFocusOnActivate(ref, isSelected);

    return (
        <>
            <Container fullGutter>
                <Button
                    aria-hidden={!isSelected}
                    data-layout-editor-focusable
                    buttonRef={ref as any}
                    tabIndex={-1}
                    data-layout-selectable={true}
                    buttonType={ButtonTypes.CUSTOM}
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.SECTION_ADD);
                        setIsModalOpen(true);
                    }}
                    ariaLabel={`Add section at position ${props.path.sectionIndex}`}
                    className={cx(classes.addSection, "addSection", { isSelected: isSelected || isModalOpen })}
                >
                    <div className={cx(classes.buttonLine)}>
                        <div className={cx(classes.buttonCircle, "buttonCircle")}>
                            <Icon icon={"data-add"} />
                        </div>
                    </div>
                </Button>
            </Container>
            <LayoutThumbnailsModal
                title={t("Choose the Type of Section")}
                exitHandler={() => {
                    setIsModalOpen(false);
                    editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.SECTION_ADD);
                }}
                sections={catalog?.sections ?? {}}
                onAddSection={(sectionID) => {
                    setIsModalOpen(false);
                    editorContents.insertSection(props.path.sectionIndex, {
                        $hydrate: sectionID,
                    });
                    editorSelection.moveSelectionTo(props.path, LayoutEditorSelectionMode.SECTION);
                }}
                isVisible={isModalOpen}
                itemType="sections"
            />
        </>
    );
});
