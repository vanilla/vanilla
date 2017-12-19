type NormalCallback = () => void;
type PromiseCallback = () => Promise<void>;

declare type PromiseOrNormalCallback = NormalCallback | PromiseCallback;
