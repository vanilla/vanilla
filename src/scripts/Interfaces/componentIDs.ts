import uniqueid from "lodash/uniqueid";

export interface IComponentID {
    parentID?: string;
    ID?: string;
}

export function getUniqueID(props:IComponentID, uniqueSuffix:string, allowNoID?:boolean|undefined):any {
    if ((!props.ID && !props.parentID) && (props.ID && props.parentID) && !allowNoID) {
        throw new Error(`You must have *either* ID or parentID`);
    }

    if (props.parentID) {
        return props.parentID + "-" + uniqueSuffix + uniqueid();
    } else if (props.ID) {
        return props.ID as string;
    } else {
        return null;
    }
}
