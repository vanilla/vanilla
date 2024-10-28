/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { useFormik } from "formik";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import Button from "@library/forms/Button";
import Translate from "@library/content/Translate";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useDiscussionPatch } from "@library/features/discussions/discussionHooks";
import { RadioGroupContext } from "@library/forms/RadioGroupContext";
import RadioButton from "@library/forms/RadioButton";

type FormValues = {
    pinLocation: "recent" | "category" | "";
};

export interface AnnounceDiscussionFormProps {
    onCancel: () => void;
    onSuccess?: () => Promise<void>;
    discussion: IDiscussion;
}

export default function AnnounceDiscussionForm({ onCancel, onSuccess, discussion }: AnnounceDiscussionFormProps) {
    const { patchDiscussion } = useDiscussionPatch(discussion.discussionID, "announce");
    const formik = useFormik<FormValues>({
        initialValues: {
            pinLocation: discussion.pinned ? discussion.pinLocation! : "",
        },
        onSubmit: async ({ pinLocation }) => {
            await patchDiscussion({
                pinLocation: pinLocation !== "" ? pinLocation : undefined,
                pinned: pinLocation !== "",
            });

            !!onSuccess && (await onSuccess());
        },
    });

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();

    const { isSubmitting, handleSubmit } = formik;

    return (
        <form onSubmit={handleSubmit}>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Announce")} />}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <>{t("Where do you want to announce this discussion?")}</>

                            <RadioGroupContext.Provider
                                value={{
                                    value: formik.values.pinLocation,
                                    onChange: (value) => {
                                        formik.setFieldValue("pinLocation", value);
                                    },
                                }}
                            >
                                <RadioButton
                                    name="pinLocation"
                                    value="category"
                                    label={
                                        <Translate
                                            source={"In <0/>."}
                                            c0={
                                                <b>
                                                    <Translate source={"<1/>"} c1={discussion.category?.name} />
                                                </b>
                                            }
                                        />
                                    }
                                />
                                {/*
                                    Need to check "discussions.moderate" because the announce permissions
                                    is consolidated to moderate here:
                                    https://github.com/vanilla/vanilla-cloud/blob/master/library/Vanilla/PermissionsTranslationTrait.php#L72
                                 */}
                                <Permission permission={["discussions.announce", "discussions.moderate"]}>
                                    <RadioButton
                                        name="pinLocation"
                                        value="recent"
                                        label={
                                            <Translate
                                                source={"In <0/> and recent discussions."}
                                                c0={
                                                    <b>
                                                        <Translate source={"<1/>"} c1={discussion.category?.name} />
                                                    </b>
                                                }
                                            />
                                        }
                                    />
                                </Permission>
                                <RadioButton
                                    name="pinLocation"
                                    value=""
                                    label={<Translate source={"Don't announce."} />}
                                />
                            </RadioGroupContext.Provider>
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={onCancel}
                            className={classFrameFooter.actionButton}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            submit
                            disabled={isSubmitting}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classFrameFooter.actionButton}
                        >
                            {isSubmitting ? <ButtonLoader /> : t("OK")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
