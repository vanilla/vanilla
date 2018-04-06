import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';
import React from 'react';

export default class SignInPage extends React.Component {
    public render() {
        return <div className="authenticateUserCol">
            <DocumentTitle classNames="isCentered" title={t('Sign In')}/>
        </div>;
    }
}



