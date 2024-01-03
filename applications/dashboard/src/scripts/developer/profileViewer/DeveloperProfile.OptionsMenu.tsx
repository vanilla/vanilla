/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDeveloperProfile } from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import {
    useDownloadDetailsMutation,
    usePatchDeveloperProfileMutation,
} from "@dashboard/developer/profileViewer/DeveloperProfiles.hooks";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownSwitchButton from "@library/flyouts/DropDownSwitchButton";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import InputTextBlock from "@library/forms/InputTextBlock";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useState } from "react";

interface IProps {
    profile: IDeveloperProfile;
}

export function DeveloperProfileOptionsMenu(props: IProps) {
    const downloadMutation = useDownloadDetailsMutation();
    const patchTrackedMutation = usePatchDeveloperProfileMutation();

    const [showRename, setShowRename] = useState(false);
    const [nameValue, setNameValue] = useState(props.profile.name);

    function clearRename() {
        setShowRename(false);
        setNameValue(props.profile.name);
    }

    const renameMutation = usePatchDeveloperProfileMutation({
        onSuccess: clearRename,
    });

    return (
        <DropDown name={"Profile Options"} flyoutType={FlyoutType.LIST} asReachPopover>
            <DropDownItemButton
                isLoading={downloadMutation.isLoading}
                disabled={downloadMutation.isLoading}
                onClick={() => {
                    downloadMutation.mutate(props.profile.developerProfileID);
                }}
            >
                Download
            </DropDownItemButton>
            <DropDownSwitchButton
                isLoading={patchTrackedMutation.isLoading}
                label={props.profile.isTracked ? "Tracked" : "Track Profile"}
                status={props.profile.isTracked}
                onClick={(e) => {
                    patchTrackedMutation.mutate({
                        developerProfileID: props.profile.developerProfileID,
                        isTracked: !props.profile.isTracked,
                    });
                }}
            />
            <DropDownItemButton
                onClick={() => {
                    setShowRename(true);
                }}
            >
                Rename
            </DropDownItemButton>
            <Modal
                isVisible={showRename}
                size={ModalSizes.MEDIUM}
                exitHandler={() => {
                    clearRename();
                }}
            >
                <Frame
                    header={<FrameHeader title={"Rename Profile"} closeFrame={clearRename} />}
                    body={
                        <FrameBody hasVerticalPadding>
                            <InputTextBlock
                                label="New Name"
                                inputProps={{
                                    value: nameValue,
                                    onChange: (e) => {
                                        setNameValue(e.target.value);
                                    },
                                }}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                onClick={() => {
                                    renameMutation.mutate({
                                        developerProfileID: props.profile.developerProfileID,
                                        name: nameValue,
                                    });
                                }}
                            >
                                {renameMutation.isLoading ? <ButtonLoader /> : "Save"}
                            </Button>
                        </FrameFooter>
                    }
                />
            </Modal>
        </DropDown>
    );
}
