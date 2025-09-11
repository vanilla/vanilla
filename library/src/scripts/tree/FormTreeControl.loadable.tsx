/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import Button from "@library/forms/Button";
import { ButtonType } from "@library/forms/buttonTypes";
import { FramedModal } from "@library/modal/FramedModal";
import ModalSizes from "@library/modal/ModalSizes";
import FormTree from "@library/tree/FormTree";
import { formTreeClasses } from "@library/tree/FormTree.classes";
import { IFormTreeControlLoadableProps } from "@library/tree/FormTreeControl.types";
import { ItemID, ITreeItem } from "@library/tree/types";
import { itemsToTree, treeToItems } from "@library/tree/utils";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { Icon, IconType } from "@vanilla/icons";
import { IControlProps } from "@vanilla/json-schema-forms";
import { FormGroupLabel } from "@vanilla/ui";
import { useEffect, useState } from "react";

interface IHideableItem {
    isHidden?: boolean;
    id?: ItemID;
    children?: IHideableItem[];
}

export default function FormTreeControl(props: IFormTreeControlLoadableProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    if (!props.control.asModal) {
        return <FormTreeControlImpl {...props} />;
    } else {
        return (
            <DashboardInputWrap>
                <Button
                    onClick={() => {
                        setIsModalOpen(true);
                    }}
                    buttonType={"standard"}
                >
                    {t("Edit")}
                </Button>
                {isModalOpen && (
                    <FramedModal
                        size={ModalSizes.LARGE}
                        onClose={() => {
                            setIsModalOpen(false);
                        }}
                        title={props.control.modalTitle}
                        onFormSubmit={() => {}}
                        footer={
                            <Button buttonType={ButtonType.TEXT_PRIMARY} type="submit">
                                {props.control.modalSubmitLabel}
                            </Button>
                        }
                    >
                        <FormTreeControlImpl {...props} />
                    </FramedModal>
                )}
            </DashboardInputWrap>
        );
    }
}

function FormTreeControlImpl(props: IFormTreeControlLoadableProps) {
    const [treeValue, setTreeValue] = useState(itemsToTree<any>(props.instance));
    const hasInstance = props.instance != null;

    const descriptionID = useUniqueID("treeDescription");

    useEffect(() => {
        // When the count of items changes try to reinitialize?
        // I'm unsure of this, and a better solution will need to be found in the future.
        // I'm quite afraid of paying the conversion cost back and forth between the items and the tree though.
        setTreeValue(itemsToTree<any>(props.instance));
    }, [hasInstance]);

    const classes = formTreeClasses();

    return (
        <>
            {!!props.control.description && (
                <FormGroupLabel
                    className={classes.treeDescription}
                    id={descriptionID}
                    description={props.control.description}
                />
            )}
            <FormTree
                aria-describedby={props.control.description ? descriptionID : undefined}
                aria-label={props.control.label as string}
                value={treeValue}
                onChange={(newTreeValue) => {
                    setTreeValue(newTreeValue);
                    props.onChange(treeToItems(newTreeValue));
                }}
                itemSchema={props.control.itemSchema}
                isItemDeletable={() => false}
                isItemHidden={(item: IHideableItem) => item.isHidden ?? false}
                isItemHideable={() => true}
                markItemHidden={(itemID, item, isHidden) => {
                    return {
                        ...item,
                        isHidden,
                    };
                }}
                getRowIcon={props.getRowIcon}
            />
        </>
    );
}
