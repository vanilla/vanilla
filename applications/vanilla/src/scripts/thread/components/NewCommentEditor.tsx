import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { metasClasses } from "@library/metas/Metas.styles";
import { MyValue } from "@library/vanilla-editor/typescript";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { discussionCommentEditorClasses } from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset.classes";
import { t } from "@vanilla/i18n";
import isEqual from "lodash-es/isEqual";
import { ReactNode } from "react";
import { RecordID } from "@vanilla/utils";
import { ToolTip } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";

export interface IDraftProps {
    draftID: RecordID;
    body: string;
    dateUpdated: string;
    format: string;
}

export enum DraftIndicatorPosition {
    WITHIN = "within",
    BELOW = "below",
}

interface CommonProps {
    value: MyValue | undefined;
    onValueChange: (value: MyValue) => void;
    onPublish: (value: MyValue) => void;
    publishLoading: boolean;
    editorKey: number;
    title?: ReactNode;
    isPreview?: boolean;
    className?: string;
    /** Any other actions  */
    tertiaryActions?: ReactNode;
    /** The post button label - Default to "Post Comment" */
    postLabel?: string;
}

type IProps = CommonProps &
    (
        | {
              onDraft?: (value: MyValue) => void;
              draft: IDraftProps | undefined;
              draftLoading?: boolean;
              draftLastSaved: Date | null;
              /** If the "save draft" button should be displayed */
              manualDraftSave?: boolean;
              draftIndicatorPosition?: DraftIndicatorPosition;
          }
        | {
              onDraft?: false;
              draft?: never;
              draftLoading?: never;
              draftLastSaved?: never;
              manualDraftSave?: never;
              draftIndicatorPosition?: never;
          }
    );

const EMPTY_DRAFT: MyValue = [{ type: "p", children: [{ text: "" }] }];

export function NewCommentEditor(props: IProps) {
    const {
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
        manualDraftSave = true,
        draftIndicatorPosition = DraftIndicatorPosition.BELOW,
    } = props;

    const classes = discussionCommentEditorClasses();

    return (
        <PageBox
            options={{
                borderType: BorderType.NONE,
            }}
            className={cx(classes.pageBox, className)}
        >
            <form
                onSubmit={async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    value && onPublish(value);
                }}
            >
                {props.title ? props.title : <PageHeadingBox title={t("Leave a Comment")} />}
                <VanillaEditor
                    key={editorKey}
                    initialFormat={draft?.format}
                    initialContent={draft?.body}
                    onChange={(newValue) => {
                        onValueChange(newValue);
                    }}
                    isPreview={props.isPreview}
                    inEditorContent={
                        <>
                            {draftLastSaved && draftIndicatorPosition === "within" && (
                                <span className={classes.draftIndicator}>
                                    <ToolTip
                                        label={
                                            <Translate
                                                source="Draft saved <0/>"
                                                c0={
                                                    <DateTime
                                                        timestamp={draftLastSaved.toUTCString()}
                                                        mode="relative"
                                                    />
                                                }
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
                />
                <div className={classes.editorPostActions}>
                    {props.tertiaryActions}
                    {draftLastSaved && draftIndicatorPosition === "below" && (
                        <span className={cx(metasClasses().metaStyle, classes.draftMessage)}>
                            {draftLoading ? (
                                t("Saving draft...")
                            ) : (
                                <Translate
                                    source="Draft saved <0/>"
                                    c0={<DateTime timestamp={draftLastSaved.toUTCString()} mode="relative" />}
                                />
                            )}
                        </span>
                    )}
                    {onDraft && manualDraftSave && (
                        <Button
                            disabled={publishLoading || draftLoading || isEqual(value, EMPTY_DRAFT)}
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
        </PageBox>
    );
}
