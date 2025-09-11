import { IDiscussion } from "@dashboard/@types/api/discussion";

export interface IConvertPostFormLoadableProps {
    onClose: () => void;
    onSuccess?: () => Promise<void>;
    discussion: IDiscussion;
}
