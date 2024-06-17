export enum ReportStatus {
    NEW = "new",
    DISMISSED = "dismissed",
    ESCALATED = "escalated",
}

export const ReportStatusLabels = {
    [ReportStatus.NEW]: "New",
    [ReportStatus.DISMISSED]: "Dismissed",
    [ReportStatus.ESCALATED]: "Escalated",
};
