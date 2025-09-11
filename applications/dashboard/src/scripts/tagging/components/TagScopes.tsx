/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITagItem } from "@dashboard/tagging/taggingSettings.types";
import { ITagScope, TagScopeService } from "@dashboard/tagging/TagScopeService";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import { useState } from "react";

function ScopeListModal(props: { tag: ITagItem; scope: ITagScope; isVisible: boolean; onClose: () => void }) {
    const { scope, tag, isVisible, onClose } = props;
    const { name: tagName } = tag;
    const { plural } = scope;

    return (
        <Modal isVisible={isVisible} exitHandler={onClose} size={ModalSizes.MEDIUM}>
            <Frame
                header={
                    <FrameHeader
                        closeFrame={onClose}
                        title={
                            <Translate
                                source={`<0/> that use <1/>`}
                                c0={plural}
                                c1={() => {
                                    return <strong>&nbsp;&quot;{tagName}&quot;&nbsp;</strong>;
                                }}
                            />
                        }
                    />
                }
                body={
                    <FrameBody hasVerticalPadding>
                        <scope.ModalContentComponent tag={tag} />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={onClose}
                            className={frameFooterClasses().actionButton}
                        >
                            {t("Close")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}

function TagItemScope(props: { scope: ITagScope; tag: ITagItem }) {
    const { scope, tag } = props;
    const { singular, plural, getIDs } = scope;
    const [modalOpen, setModalOpen] = useState(false);
    const ids = getIDs(tag);

    return (
        <>
            <button onClick={() => setModalOpen(true)}>
                <Tag preset={TagPreset.PRIMARY}>
                    <Translate
                        source={`<1>${ids!.length}</1> <2/>`}
                        c1={(text) => <b>{text}</b>}
                        c2={ids.length > 1 ? plural : singular}
                    />
                </Tag>
            </button>
            <ScopeListModal tag={tag} isVisible={modalOpen} onClose={() => setModalOpen(false)} scope={scope} />
        </>
    );
}

export default function TagScopes(props: { tagItem: ITagItem }) {
    const { tagItem } = props;

    const appliedScopes = Object.values(TagScopeService.scopes).filter((scope) => {
        const ids = scope.getIDs(tagItem);
        return ids.length > 0;
    });

    const isGlobal = appliedScopes.length === 0;

    return (
        <>
            {isGlobal && <Tag>{t("All")}</Tag>}
            {appliedScopes.map((scope) => {
                return <TagItemScope key={scope.id} scope={scope} tag={tagItem} />;
            })}
        </>
    );
}
