/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ProgressEventEmitter from "@library/utility/ProgressEventEmitter";
import { IFileAttachment } from "@library/content/attachments/Attachment";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import { EMBED_FOCUS_CLASS } from "@library/embeddedContent/embedConstants";
import { t } from "@library/utility/appUtils";
import { attachmentClasses } from "@library/content/attachments/attachmentStyles";
import { metasClasses } from "@library/metas/Metas.styles";
import { attachmentIconClasses } from "@library/content/attachments/attachmentIconsStyles";
import classNames from "classnames";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContainerSize } from "@library/embeddedContent/components/EmbedContainerSize";
import { GetAttachmentIcon } from "@library/content/attachments/attachmentUtils";

interface IProps extends IFileAttachment {
    className?: string;
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
        const classesMetas = metasClasses();
        return (
            <EmbedContainer
                size={EmbedContainerSize.SMALL}
                className={classNames("attachment", "isLoading", this.props.className, EMBED_FOCUS_CLASS)}
                aria-label={t("Uploading...")}
            >
                <div
                    className={classNames(
                        "attachment-box",
                        "attachment-loadingContent",
                        classes.loadingContent,
                        classes.box,
                    )}
                >
                    <div className={classNames("attachment-format", classes.format)}>
                        <GetAttachmentIcon type={type} className={iconClasses.root} />
                    </div>
                    <div className={classNames("attachment-main", classes.main)}>
                        <div className={classNames("attachment-title", classes.title)}>
                            {label ? label : t("Uploading...")}
                        </div>
                        {label && (
                            <div className={classNames("attachment-metas", "metas", classes.metas)}>
                                <span className={classesMetas.meta}>{t("Uploading...")}</span>
                            </div>
                        )}
                    </div>
                </div>
                <div className={classes.loadingProgress} style={{ width: `${Math.min(this.state.progress, 100)}%` }} />
            </EmbedContainer>
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
