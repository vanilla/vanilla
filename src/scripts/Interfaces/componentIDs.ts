import uniqueid from "lodash/uniqueid";

export interface IComponentID {
    parentID?: string;
    id?: string;
}

export function uniqueIDFromPrefix(uniqueSuffix:string) {
    return uniqueSuffix + uniqueid() as string;
}

export function uniqueID(props:IComponentID, uniqueSuffix:string, allowNoID?:boolean):any {
    let id:any = null;

    if (!allowNoID) {
        if ((!props.id && !props.parentID) || (props.id && props.parentID)) {
            throw new Error(`You must have *either* 'id' or 'parentID'`);
        }
    }

    if (props.parentID) {
        id = props.parentID + "-" + uniqueSuffix + uniqueid() as string;
    } else if (props.id) {
        id = props.id as string;
    }

    return id;
}
