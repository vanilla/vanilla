import { IDiscussion } from "@dashboard/@types/api/discussion";

export interface IMovePostFormLoadableProps {
    onCancel: () => void;
    onSuccess?: () => Promise<void>;
    discussion: IDiscussion;
}
