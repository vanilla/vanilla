import uniqueid from "lodash/uniqueid";

export interface IComponentID {
    parentID?: string;
    ID?: string;
}

export function uniqueIDFromPrefix(uniqueSuffix:string) {
    return uniqueSuffix + uniqueid() as string;
}

export function uniqueID(props:IComponentID, uniqueSuffix:string, allowNoID?:boolean):any {
    let id:any = null;

    if (!allowNoID) {
        if ((!props.ID && !props.parentID) || (props.ID && props.parentID)) {
            throw new Error(`You must have *either* ID or parentID`);
        }
    }

    if (props.parentID) {
        id = props.parentID + "-" + uniqueSuffix + uniqueid() as string;
    } else if (props.ID) {
        id = props.ID as string;
    }

    return id;
}
