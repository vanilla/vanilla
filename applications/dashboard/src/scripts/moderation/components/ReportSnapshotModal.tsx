/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { ReportRecordMeta } from "@dashboard/moderation/components/ReportRecordMeta";
import UserContent from "@library/content/UserContent";
import { deletedUserFragment } from "@library/features/users/constants/userFragment";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { ListItem } from "@library/lists/ListItem";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import ProfileLink from "@library/navigation/ProfileLink";
import { t } from "@vanilla/i18n";

interface IProps {
    report: IReport | null;
    isVisible: boolean;
    onClose: () => void;
}

export function ReportSnapshotModal(props: IProps) {
    const { isVisible, onClose, report } = props;
    return (
        <Modal isVisible={isVisible} exitHandler={onClose} size={ModalSizes.LARGE}>
            <Frame
                header={<FrameHeader title={report?.recordName ?? t("Report Revision")} closeFrame={onClose} />}
                body={
                    <FrameBody hasVerticalPadding>
                        {report && (
                            <ListItem
                                as={"div"}
                                icon={
                                    <ProfileLink userFragment={report.recordUser ?? deletedUserFragment()} isUserCard>
                                        <UserPhoto
                                            size={UserPhotoSize.MEDIUM}
                                            userInfo={report.recordUser ?? deletedUserFragment()}
                                        />
                                    </ProfileLink>
                                }
                                url={report.recordUrl}
                                description={<UserContent vanillaSanitizedHtml={report.recordHtml} />}
                                truncateDescription={false}
                                metas={<ReportRecordMeta record={report} />}
                            />
                        )}
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button onClick={onClose} buttonType={ButtonTypes.TEXT}>
                            {t("Close")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
