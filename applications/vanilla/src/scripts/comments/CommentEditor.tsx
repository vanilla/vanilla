/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ToolTip } from "@library/toolTip/ToolTip";
import { MyEditor, MyValue } from "@library/vanilla-editor/typescript";
import { EMPTY_RICH2_BODY } from "@library/vanilla-editor/utils/emptyRich2";
import { isMyValue } from "@library/vanilla-editor/utils/isMyValue";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { FormatConversionNotice } from "@rich-editor/editor/FormatConversionNotice";
import { focusEditor } from "@udecode/plate-common";
import { commentEditorClasses } from "@vanilla/addon-vanilla/comments/CommentEditor.classes";
import { IDraftProps } from "@vanilla/addon-vanilla/drafts/types";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { logDebug } from "@vanilla/utils";
import isEqual from "lodash-es/isEqual";
import React, { forwardRef, MutableRefObject, ReactNode, useImperativeHandle, useRef, useState } from "react";

interface CommonProps {
    value: MyValue | undefined;
    onValueChange: (value: MyValue) => void;
    onPublish: (value: MyValue) => void;
    publishLoading: boolean;
    editorKey: number;
    onCancel?: () => void;
    initialValue?: MyValue | string | undefined;
    /** Defaults to rich2 */
    format?: string;
    title?: ReactNode;
    isPreview?: boolean;
    className?: string;
    /** Any other actions  */
    tertiaryActions?: ReactNode;
    /** The post button label - Default to "Post Comment" */
    postLabel?: string;
    autoFocus?: boolean;
    containerClasses?: string;
    focusEditor?: () => void;
}

type IProps = CommonProps &
    (
        | {
              onDraft: (value: MyValue) => void;
              draft: IDraftProps["draft"];
              draftLoading: boolean;
              draftLastSaved: Date | null;
          }
        | {
              onDraft?: false;
              draft?: never;
              draftLoading?: never;
              draftLastSaved?: never;
          }
    );

export interface ICommentEditorRefHandle {
    focusCommentEditor(): void;
    formRef?: MutableRefObject<HTMLFormElement | null>;
}

/**
 * This component is the Vanilla Editor UI wrapped with draft UI handling
 * Bring your own draft and publish logic
 */
export const CommentEditor = forwardRef(function CommentEditor(
    props: IProps,
    ref: React.RefObject<ICommentEditorRefHandle>,
) {
    const {
        initialValue,
        value,
        onValueChange,
        onPublish,
        onDraft,
        draft,
        draftLoading,
        publishLoading,
        editorKey,
        draftLastSaved,
        className,
        postLabel,
        onCancel,
        format = "rich2",
    } = props;

    const classes = commentEditorClasses();

    const editorRef = useRef<MyEditor | null>(null);
    const formRef = useRef<HTMLFormElement>(null);

    useImperativeHandle(ref, () => ({
        focusCommentEditor: () => {
            if (editorRef.current) {
                focusEditor(editorRef.current);
            } else {
                logDebug("Editor ref not available");
            }
        },
        formRef: formRef,
    }));

    const [conversionDismissed, setConversionDismissed] = useState(false);
    const needsConversion = format !== "rich2";

    return (
        <form
            className={className}
            onSubmit={async (e) => {
                e.preventDefault();
                e.stopPropagation();
                value && isMyValue(value) && onPublish(value);
            }}
            ref={formRef}
        >
            {props.title && <div className={classes.draftHeaderWrapper}>{props.title}</div>}
            {needsConversion && !conversionDismissed && (
                <FormatConversionNotice
                    className={classes.formatNoticeLayout}
                    onCancel={onCancel}
                    onConfirm={() => setConversionDismissed(true)}
                />
            )}
            <VanillaEditor
                editorRef={editorRef}
                containerClasses={props.containerClasses}
                autoFocus={props.autoFocus}
                key={editorKey}
                initialFormat={draft ? draft?.format : needsConversion ? "html" : format}
                initialContent={draft ? draft?.body : initialValue}
                onChange={(newValue) => {
                    onValueChange(newValue);
                }}
                isPreview={props.isPreview}
                inEditorContent={
                    <>
                        {draftLastSaved && (
                            <span className={classes.draftIndicator}>
                                <ToolTip
                                    label={
                                        <Translate
                                            source="Draft saved <0/>"
                                            c0={<DateTime timestamp={draftLastSaved.toUTCString()} mode="relative" />}
                                        />
                                    }
                                >
                                    <span>
                                        <Icon icon={"data-checked"} />
                                    </span>
                                </ToolTip>
                            </span>
                        )}
                    </>
                }
                needsHtmlConversion={needsConversion}
            />
            <div className={classes.editorPostActions}>
                {props.tertiaryActions}
                {onDraft && (
                    <Button
                        disabled={publishLoading || draftLoading || isEqual(value, EMPTY_RICH2_BODY)}
                        buttonType={ButtonTypes.STANDARD}
                        onClick={() => onDraft && value && onDraft(value)}
                    >
                        {t("Save Draft")}
                    </Button>
                )}
                <Button disabled={publishLoading} submit buttonType={ButtonTypes.PRIMARY}>
                    {publishLoading ? <ButtonLoader /> : postLabel ? t(postLabel) : t("Post Comment")}
                </Button>
            </div>
        </form>
    );
});
