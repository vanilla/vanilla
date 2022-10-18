/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentProps, useState } from "react";
import { t } from "@vanilla/i18n";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ILayoutCatalog, LayoutSectionID } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Button from "@library/forms/Button";
import ModalSizes from "@library/modal/ModalSizes";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { useUniqueID } from "@library/utility/idUtils";
import LayoutSectionsThumbnails from "@dashboard/layout/editor/thumbnails/LayoutSectionsThumbnails";
import LayoutWidgetsThumbnails from "@dashboard/layout/editor/thumbnails/LayoutWidgetsThumbnails";
import { layoutThumbnailsClasses } from "@dashboard/layout/editor/thumbnails/LayoutThumbnails.classes";

interface ILayoutThumbnailsModalProps<T extends "sections" | "widgets"> {
    title?: string;
    sections: ILayoutCatalog[T];
    isVisible: ComponentProps<typeof Modal>["isVisible"];
    exitHandler: ComponentProps<typeof Modal>["exitHandler"];
    onAddSection: (sectionID: string) => void;
    itemType: "sections" | "widgets";
    selectedSection?: string;
}

export function LayoutThumbnailsModal<T extends "sections" | "widgets">(props: ILayoutThumbnailsModalProps<T>) {
    const { title, sections, isVisible, exitHandler, onAddSection, itemType, selectedSection } = props;
    const [selectedSectionID, setSelectedSectionID] = useState<string>(selectedSection ?? Object.keys(sections)[0]);
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const titleID = useUniqueID("layoutThumbnail");

    return (
        <Modal noFocusOnExit isVisible={isVisible} size={ModalSizes.LARGE} exitHandler={exitHandler}>
            <form
                data-layout-editor-modal
                className={layoutThumbnailsClasses().form}
                onSubmit={(e) => {
                    e.preventDefault();
                    onAddSection(selectedSectionID);
                }}
            >
                <Frame
                    header={<FrameHeader titleID={titleID} closeFrame={exitHandler} title={title} />}
                    body={
                        <FrameBody>
                            <div className={classesFrameBody.contents}>
                                {itemType === "sections" ? (
                                    <LayoutSectionsThumbnails
                                        labelID={titleID}
                                        sections={sections}
                                        onChange={setSelectedSectionID}
                                        value={selectedSectionID}
                                    />
                                ) : (
                                    <LayoutWidgetsThumbnails
                                        labelID={titleID}
                                        widgets={sections}
                                        onChange={setSelectedSectionID}
                                        value={selectedSectionID}
                                    />
                                )}
                            </div>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button
                                buttonType={ButtonTypes.TEXT}
                                onClick={exitHandler}
                                className={classFrameFooter.actionButton}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button buttonType={ButtonTypes.TEXT} submit className={classFrameFooter.actionButton}>
                                {t("Add")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
