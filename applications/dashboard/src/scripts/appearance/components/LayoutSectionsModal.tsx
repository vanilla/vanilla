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
import LazyModal from "@library/modal/LazyModal";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Button from "@library/forms/Button";
import ModalSizes from "@library/modal/ModalSizes";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import Modal from "@library/modal/Modal";
import { useUniqueID } from "@library/utility/idUtils";
import LayoutSectionsThumbnails from "@dashboard/appearance/thumbnails/LayoutSectionsThumbnails";
import { layoutSectionsModalClasses } from "@dashboard/appearance/components/LayoutSectionsModal.classes";

interface ILayoutSectionsModalProps {
    title?: string;
    sections: ILayoutCatalog["sections"];
    isVisible: ComponentProps<typeof Modal>["isVisible"];
    exitHandler: ComponentProps<typeof Modal>["exitHandler"];
    onAddSection: (sectionID: LayoutSectionID) => void;
}

export function LayoutSectionsModal(props: ILayoutSectionsModalProps) {
    const { title, sections, isVisible, exitHandler, onAddSection } = props;
    const [selectedSectionID, setSelectedSectionID] = useState<LayoutSectionID>("react.section.full-width");
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const titleID = useUniqueID("layoutSectionTitle");
    return (
        <LazyModal noFocusOnExit isVisible={isVisible} size={ModalSizes.LARGE} exitHandler={exitHandler}>
            <form
                className={layoutSectionsModalClasses().form}
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
                                <LayoutSectionsThumbnails
                                    labelID={titleID}
                                    sections={sections}
                                    onChange={setSelectedSectionID}
                                    value={selectedSectionID}
                                />
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
        </LazyModal>
    );
}
