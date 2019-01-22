/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";

import { t } from "@library/application";
import classNames from "classnames";
import CloseButton from "@library/components/CloseButton";
import { IFileAttachment } from "@library/components/attachments/Attachment";
import { getAttachmentIcon, AttachmentType } from "@library/components/attachments";
import ProgressEventEmitter from "@library/ProgressEventEmitter";
import { FOCUS_CLASS } from "@library/embeds";

interface IProps extends IFileAttachment {
    type: AttachmentType;
    size: number; // bytes
    progressEventEmitter?: ProgressEventEmitter;
}

interface IState {
    progress: number; // 0 - 100.
}

/**
 * Implements file attachment item
 */
export default class AttachmentLoading extends React.Component<IProps, IState> {
    public state: IState = {
        progress: 0,
    };

    public render() {
        const { title, name, type } = this.props;
        const label = title || name;
        return (
            <div
                className={classNames("attachment", "isLoading", this.props.className, FOCUS_CLASS)}
                tabIndex={0}
                aria-label={t("Uploading...")}
            >
                <div className="attachment-box attachment-loadingContent">
                    <div className="attachment-format">{getAttachmentIcon(type)}</div>
                    <div className="attachment-main">
                        <div className="attachment-title">{label}</div>
                        <div className="attachment-metas metas">
                            <span className="meta">{t("Uploading...")}</span>
                        </div>
                    </div>
                    <CloseButton
                        title={t("Cancel")}
                        className="attachment-close"
                        onClick={this.props.deleteAttachment}
                    />
                </div>
                <div
                    className="attachment-loadingProgress"
                    style={{ width: `${Math.min(this.state.progress, 100)}%` }}
                />
            </div>
        );
    }

    public componentDidMount() {
        const emitter = this.props.progressEventEmitter;
        emitter instanceof ProgressEventEmitter && emitter.addEventListener(this.onProgressEvent);
    }

    public componentWillUnmount() {
        const emitter = this.props.progressEventEmitter;
        emitter instanceof ProgressEventEmitter && emitter.removeEventListener(this.onProgressEvent);
    }

    private onProgressEvent = (event: ProgressEvent) => {
        const calculatedPercentage = event.loaded / event.total;
        const progress = Math.round(calculatedPercentage * 100);
        this.setState({ progress });
    };
}
