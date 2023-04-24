/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import { ICollectionResource } from "@library/featuredCollections/Collections.variables";
import { collectionsFormClasses } from "@library/featuredCollections/CollectionsForm.styles";
import {
    useCollectionList,
    useCollectionsByResource,
    useCollectionsStatusByResource,
    usePostCollectionsByResource,
    usePutCollectionsByResource,
} from "@library/featuredCollections/collectionsHooks";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import InputBlock from "@library/forms/InputBlock";
import { Tokens } from "@library/forms/select/Tokens";
import { TextInput } from "@library/forms/TextInput";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";
import { useLastValue } from "@vanilla/react-utils";
import { useFormik } from "formik";
import sortBy from "lodash/sortBy";
import uniq from "lodash/uniq";
import React, { useEffect, useMemo, useRef, useState } from "react";

type FormValues = {
    collections: IComboBoxOption[];
    newCollections: string[];
};

interface IProps extends ICollectionResource {
    onSuccess: () => void;
    onCancel: () => void;
}

const collectionToOption = ({ collectionID, name }) => ({
    label: name,
    value: collectionID,
});

export function CollectionsFormLoadable(props: IProps) {
    const { onSuccess, onCancel, recordID, recordType } = props;
    const [options, setOptions] = useState<IComboBoxOption[]>([]);
    const classesFrameBody = frameBodyClasses();
    const classesFrameFooter = frameFooterClasses();
    const classes = collectionsFormClasses();
    const collections = useCollectionList();
    const resource = { recordID, recordType };
    const resourceCollections = useCollectionsByResource(resource);
    const collectionsStatus = useCollectionsStatusByResource(resource);
    const putCollections = usePutCollectionsByResource(resource);
    const postCollections = usePostCollectionsByResource(resource);
    const { addToast } = useToast();

    // Update the options list when the list of existing options is updated
    useEffect(() => {
        if (collections.status === LoadStatus.SUCCESS && options.length !== collections.data?.length) {
            const tmpOptions = sortBy((collections.data ?? []).map(collectionToOption), ({ label }) =>
                label.toLowerCase(),
            );
            setOptions(tmpOptions);
        }
    }, [collections]);

    // get the currently assigned collections and set as the initial form value
    useEffect(() => {
        if (
            resourceCollections.status === LoadStatus.SUCCESS &&
            resourceCollections.data &&
            resourceCollections.data.length > 0
        ) {
            setValues({
                ...values,
                collections: sortBy(resourceCollections.data.map(collectionToOption), ({ label }) =>
                    label.toLowerCase(),
                ),
            });
        } else {
            setValues({
                ...values,
                collections: [],
            });
        }
    }, [resourceCollections]);

    const formik = useFormik<FormValues>({
        initialValues: {
            collections: [],
            newCollections: [],
        },
        onSubmit: async (formValues, helpers) => {
            const previousCollectionIDs = resourceCollections.data
                ? resourceCollections.data?.map(({ collectionID }) => collectionID)
                : [];
            const collectionIDs = formValues.collections.map(({ value }) => value);
            // filter out blank values from the list of new collections
            const newCollections = formValues.newCollections.filter((name) => isFieldValid(name) && name.length > 0);
            // get the collections that the resource was removed from
            const removeCollections = previousCollectionIDs.filter((id) => collectionIDs.indexOf(id) < 0);
            // get the existing collections that the resource was added to
            const addCollections = collectionIDs.filter((id) => previousCollectionIDs?.indexOf(id) < 0);

            try {
                await putCollections(collectionIDs);

                if (newCollections.length > 0) {
                    await postCollections(newCollections);
                }

                const addCollectionCount = addCollections.length + newCollections.length;
                const removeCollectionCount = removeCollections.length;
                const addMessage = addCollectionCount > 1 ? "Added to <0/> collections" : "Added to 1 collection.";
                const removeMessage =
                    removeCollectionCount > 1 ? "Removed from <0/> collections." : "Removed from 1 collection.";

                addToast({
                    autoDismiss: true,
                    body: (
                        <>
                            {t("Success!")}
                            {addCollectionCount > 0 && (
                                <>
                                    {" "}
                                    <Translate source={addMessage} c0={addCollectionCount} />
                                </>
                            )}
                            {removeCollectionCount > 0 && (
                                <>
                                    {" "}
                                    <Translate source={removeMessage} c0={removeCollectionCount} />
                                </>
                            )}
                        </>
                    ),
                });
            } catch (error) {
                addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: <>{error.message}</>,
                });
            } finally {
                onSuccess();
                helpers.resetForm();
            }
        },
    });

    const { values, setFieldValue, handleSubmit, isSubmitting, setValues } = formik;

    const hasDuplicates = useMemo<boolean>(() => {
        const fullList = [...options.map(({ label }) => label), ...values.newCollections]
            .filter((name) => name !== "")
            .map((name) => name.toLowerCase());

        return uniq(fullList).length !== fullList.length;
    }, [values.newCollections, options]);

    const handleAddCollection = () => {
        const { newCollections } = values;
        setFieldValue("newCollections", [...newCollections, ""]);
    };

    const lastCollectionRef = useRef<HTMLInputElement | null>(null);
    const countNewCollections = formik.values.newCollections.length;
    const lastCountNewCollections = useLastValue(countNewCollections);
    useEffect(() => {
        if (countNewCollections > (lastCountNewCollections ?? countNewCollections)) {
            // We just added a collection.
            lastCollectionRef.current?.focus();
        }
    }, [countNewCollections, lastCountNewCollections]);

    const handleDeleteCollection = (idx: number) => {
        const newCollections = values.newCollections.filter((_, index) => index !== idx);
        setFieldValue("newCollections", newCollections);
    };

    const changeNewCollection = (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const {
            currentTarget: { id, value },
        } = event;
        const { newCollections } = values;
        const [collectionIdx] = id.split("-");
        const tmpList = [...newCollections];
        tmpList[collectionIdx] = value;
        setFieldValue("newCollections", tmpList);
    };

    // Check to ensure that the new collection name isn't already in use
    // Case insensitive
    // Also checks other "New Collection" fields
    const isFieldValid = (value: string): boolean => {
        if (isSubmitting || value === "") return true;

        const { newCollections } = values;
        const fullList = [...options.map(({ label }) => label), ...newCollections];

        return fullList.filter((name) => name.toLowerCase() === value.toLowerCase()).length <= 1;
    };

    return (
        <form onSubmit={handleSubmit}>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Add to Collections")} />}
                body={
                    <FrameBody>
                        <div className={cx(classesFrameBody.contents, classes.formBody)}>
                            <Tokens
                                label={t("Select Existing Collections")}
                                options={options}
                                value={values.collections}
                                maxHeight={130}
                                showIndicator
                                onChange={(value) => setFieldValue("collections", value)}
                                hideSelectedOptions={false}
                            />
                            {values.newCollections.length > 0 && (
                                <InputBlock label={t("Create New Collection")}>
                                    {values.newCollections.map((name, idx) => (
                                        <div key={idx}>
                                            <div className={classes.newCollection}>
                                                <TextInput
                                                    inputRef={
                                                        idx === countNewCollections - 1 ? lastCollectionRef : undefined
                                                    }
                                                    id={`${idx}-newCollection`}
                                                    aria-label={t(`New Collection ${idx + 1}`)}
                                                    className={cx(!isFieldValid(name) && "hasError")}
                                                    value={name}
                                                    onChange={changeNewCollection}
                                                    disabled={isSubmitting}
                                                />
                                                <Button
                                                    buttonType={ButtonTypes.ICON}
                                                    className={classes.deleteNewCollectionButton}
                                                    onClick={() => handleDeleteCollection(idx)}
                                                >
                                                    <Icon icon="analytics-remove" />
                                                </Button>
                                            </div>
                                            {/* Displays the loading status of the new collection as it is saving */}
                                            {collectionsStatus[name]?.status && (
                                                <p
                                                    className={cx(
                                                        classes.newCollectionStatus,
                                                        collectionsStatus[name].status,
                                                    )}
                                                >
                                                    {t(collectionsStatus[name].status)}
                                                </p>
                                            )}
                                            {!isFieldValid(name) && (
                                                <ErrorMessages
                                                    padded
                                                    errors={[
                                                        {
                                                            message: t(`A collection named "${name}" already exists.`),
                                                        },
                                                    ]}
                                                />
                                            )}
                                        </div>
                                    ))}
                                </InputBlock>
                            )}
                            <div className={classes.addNewWrapper}>
                                <Button
                                    className={classes.addNewButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={handleAddCollection}
                                >
                                    <Icon icon="analytics-add" /> {t("Add New Collection")}
                                </Button>
                            </div>
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={onCancel}
                            className={classesFrameFooter.actionButton}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            submit
                            disabled={isSubmitting || hasDuplicates}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classesFrameFooter.actionButton}
                        >
                            {isSubmitting ? <ButtonLoader /> : t("Submit")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}

export default CollectionsFormLoadable;
