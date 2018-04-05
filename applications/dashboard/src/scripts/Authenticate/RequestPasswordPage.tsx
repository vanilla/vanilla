import { t } from '@core/application';
import DocumentTitle from '@core/Components/DocumentTitle';
import React from 'react';

export default class RequestPasswordPage extends React.Component {
    public render() {
        return <DocumentTitle title={t('Sign In')}/>;
    }
}
