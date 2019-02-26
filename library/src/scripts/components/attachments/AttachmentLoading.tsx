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
import { attachmentClasses } from "@library/styles/attachmentStyles";
import { attachmentIconClasses } from "@library/styles/attachmentIconsStyles";

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
        const classes = attachmentClasses();
        const iconClasses = attachmentIconClasses();
        return (
            <div
                className={classNames("attachment", "isLoading", this.props.className, FOCUS_CLASS, classes.root)}
                tabIndex={0}
                aria-label={t("Uploading...")}
            >
                <div className={classNames("attachment-box", "attachment-loadingContent", classes.box)}>
                    <div className={classNames("attachment-format", classes.format)}>
                        {getAttachmentIcon(type, iconClasses.root)}
                    </div>
                    <div className={classNames("attachment-main", classes.main)}>
                        <div className={classNames("attachment-title", classes.title)}>
                            {label ? label : t("Uploading...")}
                        </div>
                        {label && (
                            <div className={classNames("attachment-metas", "metas", classes.metas)}>
                                <span className="meta">{t("Uploading...")}</span>
                            </div>
                        )}
                    </div>
                    <CloseButton
                        title={t("Cancel")}
                        className={classNames("attachment-close", classes.close)}
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
