
import React from 'react';

import Spacer from '../../../common/components/spacer';
import FlexContainer from '../../../common/components/flex-container';
import FormIntegrationAdd from './forms/integrations/index';

import toastr from 'toastr';

import GoaState from '../../../common/goa-state';


import ActionPanel from '../../../common/portal/panel/action';
import PanelSearchTable from '../../../common/portal/panel/search-table';

const INTEGRATION_PROPERTIES = {
    reference: {
        title: 'Name',
        property: 'name',
        type: 'TEXT',
        default: true
    },
    unique: {
        title: 'Unique Key',
        property: 'store_unique_key',
        type: 'TEXT',
        default: true
    },
    store: {
        title: 'Marketplace',
        property: 'store',
        type: 'TEXT',
        default: true
    },
    status: {
        title: 'Status',
        property: 'status',
        type: 'TEXT',
        default: true
    },
    refreshed: {
        title: 'Last Refresh',
        property: 'refreshed_at',
        type: 'DATETIME',
        default: true
    }
}


export default class Integrations extends React.Component {

    constructor(props)
    {
        super(props);
        this.state = {};

        this.handleConnectIntegration = this.handleConnectIntegration.bind(this);

        this.handleShowAdd = this.handleShowAdd.bind(this);
        this.handleRefreshAll = this.handleRefreshAll.bind(this);
        this.handleRefresh = this.handleRefresh.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
    }

    handleConnectIntegration(type) {
        GoaApi.Integration.connect({type: type}, success => {
            console.log(success)
        }, failure => {
            toastr.error(failure);
        })
    }

    handleShowAdd() {
        GoaState.set('active-modal', {
            component: <FormIntegrationAdd 
                onAdd={() => {
                    if (this.integrationsTable) this.integrationsTable.handleSearch();
                }}
                onCancel={() => GoaState.empty('active-modal')}
            />
        });
    }

    handleRefreshAll() {
        toastr.warning('Refreshing all stores');
        GoaApi.Integration.syncAll({}, success => {
            toastr.success('Successfully refreshed all stores');
            if (this.integrationsTable) this.integrationsTable.handleSearch();
        }, failure => {
            toastr.error(failure.message);
        });
    }

    handleRefresh(model) {
        toastr.warning('Refreshing store: ' + model.name);
        GoaApi.Integration.syncOrders({id: model.id}, success => {
            toastr.success('Successfully refreshed store');
            if (this.integrationsTable) this.integrationsTable.handleSearch();
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleDelete(model) {

    }

    render() {
        return (
            <React.Fragment>
                <FlexContainer> 
                    <ActionPanel label='Add an integration' icon='fa fa-link' onClick={this.handleShowAdd}/>
                    <ActionPanel label='Refresh all' icon='fa fa-refresh' onClick={this.handleRefreshAll}/>
                </FlexContainer>
                <Spacer space='20px'/>
                <PanelSearchTable
                    ref={e => this.integrationsTable = e}
                    properties={INTEGRATION_PROPERTIES}
                    tableKey='Integrations'
                    tableTitle='Integrations'
                    tableIcon='fa fa-link'
                    searchMethod={GoaApi.Integration.search}
                    onRefresh={this.handleRefresh}
                    onDelete={this.handleDelete}
                />      
            </React.Fragment>
        )
    }
}
