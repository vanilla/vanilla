/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import previewCardClasses from "@library/theming/PreviewCard.styles";
import PreviewCard, { IPreviewCardProps } from "@library/theming/PreviewCard";
import Button from "@library/forms/Button";
import { t } from "@library/utility/appUtils";
import ModalConfirm from "@library/modal/ModalConfirm";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";

export interface ILayoutPreviewCardProps {
    previewImage: IPreviewCardProps["previewImage"];
    active?: IPreviewCardProps["active"];
    onApply?: VoidFunction;
    editUrl?: string;
}

function LayoutPreviewCard(props: ILayoutPreviewCardProps) {
    const [applyModalVisible, setApplyModalVisible] = useState(false);
    const [editModalVisible, setEditModalVisible] = useState(false);
    const { previewImage, active, editUrl, onApply } = props;
    const classes = previewCardClasses();

    const containerRef: React.RefObject<HTMLDivElement> = React.createRef();

    const actionButtons =
        !active || !!editUrl ? (
            <>
                {!active && !!onApply && (
                    <>
                        <Button
                            className={classes.actionButton}
                            onClick={() => {
                                if (editUrl) {
                                    containerRef.current?.focus();
                                    setApplyModalVisible(true);
                                } else {
                                    containerRef.current?.focus();
                                    onApply?.();
                                }
                            }}
                        >
                            {t("Apply")}
                        </Button>
                        <ModalConfirm
                            isVisible={applyModalVisible}
                            title={t("Apply Layout")}
                            onCancel={() => setApplyModalVisible(false)}
                            onConfirm={() => {
                                onApply?.();
                                setApplyModalVisible(false);
                            }}
                            confirmTitle={t("Apply")}
                        >
                            <Translate
                                source="You are about to apply a new layout option. This can be customized and previewed using our Theme Editor. <0>Learn more</0>."
                                c0={(content) => (
                                    <SmartLink to="https://success.vanillaforums.com/kb/articles/279">
                                        {content}
                                    </SmartLink>
                                )}
                            />
                        </ModalConfirm>
                    </>
                )}

                {!!editUrl && (
                    <>
                        <Button className={classes.actionButton} onClick={() => setEditModalVisible(true)}>
                            {t("Edit")}
                        </Button>
                        <ModalConfirm
                            isVisible={editModalVisible}
                            title={t("Edit Layout")}
                            onCancel={() => setEditModalVisible(false)}
                            confirmLinkTo={editUrl}
                            confirmTitle={t("Continue")}
                        >
                            <Translate
                                source="This layout can be customized using our new Theme Editor. <0>Learn more.</0>"
                                c0={(content) => (
                                    <SmartLink to="https://success.vanillaforums.com/kb/articles/279">
                                        {content}
                                    </SmartLink>
                                )}
                            />
                        </ModalConfirm>
                    </>
                )}
            </>
        ) : null;

    return (
        <PreviewCard previewImage={previewImage} ref={containerRef} actionButtons={actionButtons} active={!!active} />
    );
}

export default LayoutPreviewCard;
