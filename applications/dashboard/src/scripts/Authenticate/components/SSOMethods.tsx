import { t } from '@core/application';
import React from 'react';
import Paragraph from '../../Forms/Paragraph';
import Or from '../../Forms/Or';
import {getUniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IProps extends IComponentID {
    includeOr?: boolean;
}

export default class SSOMethods extends React.Component<IProps> {
    public methods: any[];
    public includeOr: boolean;
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = getUniqueID(props, 'SSOMethods', true);
        this.includeOr = props.includeOr || true;

        this.methods = [{
            authenticatorID: "facebookID",
            type: "facebook",
            isUnique: true,
            name: "facebook",
            resourceUrl: "#",
            signInUrl: "#",
            registerUrl: "#",
            signOutUrl: "#",
            ui: {
                url: "#",
                photoUrl: "data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\"><title>Facebook</title><path fill=\"white\" d=\"M12,24 C5.372583,24 0,18.627417 0,12 C0,5.372583 5.372583,0 12,0 C18.627417,0 24,5.372583 24,12 C24,18.627417 18.627417,24 12,24 Z M17.8381312,5.4375 L6.1619625,5.4375 C5.76178125,5.4375 5.4375,5.7616875 5.4375,6.1619625 L5.4375,17.8381312 C5.4375,18.2382188 5.76178125,18.5625 6.1619625,18.5625 L12.4478813,18.5625 L12.4478813,13.4798625 L10.7374875,13.4798625 L10.7374875,11.498925 L12.4478813,11.498925 L12.4478813,10.0382062 C12.4478813,8.342925 13.4833313,7.41980625 14.9957813,7.41980625 C15.7199625,7.41980625 16.3425375,7.47373125 16.5239813,7.4979 L16.5239813,9.26953125 L15.47535,9.26998125 C14.6526563,9.26998125 14.493525,9.660825 14.493525,10.2343125 L14.493525,11.498925 L16.4549625,11.498925 L16.1995312,13.4798625 L14.493525,13.4798625 L14.493525,18.5625 L17.8381313,18.5625 C18.2382188,18.5625 18.5625,18.2382188 18.5625,17.8381312 L18.5625,6.1619625 C18.5625,5.7616875 18.2382188,5.4375 17.8381312,5.4375 Z\"></path></svg>",
                buttonName: "Sign in with Facebook",
                backgroundColor: "#3b5998",
                foregroundColor: "#fff",
            },
            sso: {
                isTrusted: true,
                canSignIn: true,
                canLinkSession: true,
                canAutoLinkUser: true,
            },
            isActive: true,
            attributes: {},
        },{
            authenticatorID: "twitterID",
            type: "twitter",
            isUnique: true,
            name: "twitter",
            resourceUrl: "#",
            signInUrl: "#",
            registerUrl: "#",
            signOutUrl: "#",
            ui: {
                url: "#",
                photoUrl: "data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\"><title>Facebook</title><path fill=\"white\" d=\"M12,24 C5.372583,24 0,18.627417 0,12 C0,5.372583 5.372583,0 12,0 C18.627417,0 24,5.372583 24,12 C24,18.627417 18.627417,24 12,24 Z M17.8381312,5.4375 L6.1619625,5.4375 C5.76178125,5.4375 5.4375,5.7616875 5.4375,6.1619625 L5.4375,17.8381312 C5.4375,18.2382188 5.76178125,18.5625 6.1619625,18.5625 L12.4478813,18.5625 L12.4478813,13.4798625 L10.7374875,13.4798625 L10.7374875,11.498925 L12.4478813,11.498925 L12.4478813,10.0382062 C12.4478813,8.342925 13.4833313,7.41980625 14.9957813,7.41980625 C15.7199625,7.41980625 16.3425375,7.47373125 16.5239813,7.4979 L16.5239813,9.26953125 L15.47535,9.26998125 C14.6526563,9.26998125 14.493525,9.660825 14.493525,10.2343125 L14.493525,11.498925 L16.4549625,11.498925 L16.1995312,13.4798625 L14.493525,13.4798625 L14.493525,18.5625 L17.8381313,18.5625 C18.2382188,18.5625 18.5625,18.2382188 18.5625,17.8381312 L18.5625,6.1619625 C18.5625,5.7616875 18.2382188,5.4375 17.8381312,5.4375 Z\"></path></svg>",
                buttonName: "Sign in with Twitter",
                backgroundColor: "#3b5998",
                foregroundColor: "#fff",
            },
            sso: {
                isTrusted: true,
                canSignIn: true,
                canLinkSession: true,
                canAutoLinkUser: true,
            },
            isActive: true,
            attributes: {},
        }];
    }

    public handleClick (method):any {
        window.console.log("do sign in");
    }

    public render() {
        if (this.methods.length === 0) {
            return null;
        } else {
            let longestText = 0;
            const or = this.includeOr ? <Or/> : null;
            const ssoMethods = this.methods.map((method, index) => {
                const nameLength = t(method.ui.buttonName).length;
                if ( nameLength > longestText) {
                    longestText = nameLength;
                }

                const methodStyles = {
                    backgroundColor: method.ui.backgroundColor,
                    color: method.ui.foregroundColor,
                };

                const labelStyles = {
                    minWidth: `calc(36px + ${longestText + 2}ex)`
                };

                const buttonClick = () => {
                    this.handleClick(method);
                };

                return <button type="button" key={ index } onClick={buttonClick} className="BigButton button Button button-sso button-fullWidth bg-facebook" style={methodStyles}>
                    <span className="button-ssoContents" style={labelStyles}>
                        <img src={method.ui.photoUrl} className="ssoMethod-icon" aria-hidden={true} />
                        <span className="button-ssoLabel">
                            {t(method.ui.buttonName)}
                        </span>
                    </span>
                </button>;
            });

            return <div className="ssoMethods">
                <Paragraph parentID={this.ID} content={t('Sign in with one of the following:')} />
                {ssoMethods}
                {or}
            </div>;
        }
    }
}
