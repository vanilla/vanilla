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

interface IDraftProps {
    draftID: number;
    body: string;
    dateUpdated: string;
    format: string;
}

interface CommonProps {
    value: MyValue | undefined;
    onValueChange: (value: MyValue) => void;
    onPublish: (value: MyValue) => void;
    publishLoading: boolean;
    editorKey: number;
    title?: ReactNode;
    isPreview?: boolean;
}

type IProps = CommonProps &
    (
        | {
              onDraft: (value: MyValue) => void;
              draft: IDraftProps | undefined;
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

const EMPTY_DRAFT: MyValue = [{ type: "p", children: [{ text: "" }] }];

export function NewCommentEditor(props: IProps) {
    const { value, onValueChange, onPublish, onDraft, draft, draftLoading, publishLoading, editorKey, draftLastSaved } =
        props;

    const classes = discussionCommentEditorClasses();

    return (
        <PageBox
            options={{
                borderType: BorderType.NONE,
            }}
            className={classes.pageBox}
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
                />
                <div className={classes.editorPostActions}>
                    {draftLastSaved && (
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
                    {onDraft && (
                        <Button
                            disabled={publishLoading || draftLoading || isEqual(value, EMPTY_DRAFT)}
                            buttonType={ButtonTypes.STANDARD}
                            onClick={() => onDraft && value && onDraft(value)}
                        >
                            {t("Save Draft")}
                        </Button>
                    )}
                    <Button disabled={publishLoading} submit buttonType={ButtonTypes.PRIMARY}>
                        {publishLoading ? <ButtonLoader /> : t("Post Comment")}
                    </Button>
                </div>
            </form>
        </PageBox>
    );
}
