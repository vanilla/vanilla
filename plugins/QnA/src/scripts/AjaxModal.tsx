/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode, useEffect, useState } from "react";
import apiv2 from "@library/apiv2";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import FrameBody from "@library/layout/frame/FrameBody";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { t } from "@vanilla/i18n/src";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";

interface IProps {
    url: string;
    data: object;
    loader: React.ReactNode;
    size: ModalSizes;
    title?: string;
}

/**
 * A modal component that displays the content after an ajax call
 * Extends the Modal component
 *
 * @param props
 * @constructor
 */
export function AjaxModal(props: IProps) {
    const [isVisible, setIsVisible] = useState(true);
    const close = () => setIsVisible(false);
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState<ReactNode | boolean>(false);

    useEffect(() => {
        if (!loading && !message) {
            apiv2
                .post(props.url, props.data)
                .then((response) => {
                    if (response.data.message) {
                        if (response.data.notificationDate) {
                            setMessage(
                                <div style={{ padding: 16 }}>
                                    <Translate
                                        source="A follow-up email was already sent on  <0/>."
                                        c0={<DateTime timestamp={response.data.notificationDate} />}
                                    />
                                </div>,
                            );
                        } else {
                            setMessage(
                                <div style={{ padding: 16 }}>
                                    <Translate
                                        source="<0/> notifications sent successfully."
                                        c0={response.data.notificationsSent}
                                    />
                                </div>,
                            );
                        }
                    } else {
                        setIsVisible(false);
                    }
                    setLoading(false);
                })
                .catch((response) => {
                    setMessage(response.data.message);
                });
            setLoading(true);
        }
    });

    return (
        <Modal isVisible={isVisible} size={props.size} exitHandler={close}>
            <Frame
                header={props.title ? <FrameHeader title={props.title} closeFrame={close} /> : null}
                body={<FrameBody selfPadded={true}>{loading ? props.loader : message}</FrameBody>}
            />
        </Modal>
    );
}
