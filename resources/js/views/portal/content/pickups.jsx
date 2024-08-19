import React from 'react';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';

import GoaState from '../../../common/goa-state';


import Functions from '../../../common/functions';

import ActionPanel from '../../../common/portal/panel/action';

import FormAddPickup from './forms/add-pickup';
//import FormAddScanForm from './forms/add-scan-form';
//import ScanFormModel from '../../../common/models/scanform/model';


const PROPERTIES = {
    confirmation: {
        title: 'Confirmation',
        property: 'confirmation_number',
        type: 'TEXT',
        default: 'true'
    },
    scheduled: {
        title: 'Scheduled Date',
        property: 'date',
        type: 'DATETIME',
        default: 'true'
    },
    dayofweek: {
        title: 'Day Of Week',
        property: 'day_of_week',
        type: 'TEXT',
        default: 'true'
    },
    status: {
        title: 'Status',
        property: 'status',
        type: 'TEXT',
        default: 'true'
    },
    countlabels: {
        title: 'Label Count',
        property: 'count_label_total',
        type: 'TEXT',
        default: 'true'
    },
    countscanforms: {
        title: 'Scan Form Count',
        property: 'count_scan_form',
        type: 'TEXT',
        default: 'true'
    },
    formatted: {
        title: 'Address',
        type: 'METHOD',
        method: model => {
            if (!model.from_address) return '-';
            return model.from_address.name + ' - ' + Functions.formatAddress(model.from_address)
        },
        default: 'true'
    },
    created: {
        title: 'Created',
        type: 'DATETIME',
        property: 'created_at',
        default: 'true'
    }
}

export default class Pickups extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
        }

        this.handleAdd = this.handleAdd.bind(this);
        this.handleShowAdd = this.handleShowAdd.bind(this);
        this.handleSelect = this.handleSelect.bind(this);
    }


    handleAdd(model) {
        if (this.table) this.table.handleSearch();
        GoaState.set('active-modal', { component: undefined, className: undefined });
        //this.handleSelect(model)
    }

    handleSelect(model) {
        return;
        GoaState.set('active-modal', {
            component: <ScanFormModel
                model={model}
                onClose={() => GoaState.empty('active-modal')}
            />
        })
    }

    handleShowAdd() {
        GoaState.set('active-modal', {
            component: <FormAddPickup
                onAdd={this.handleAdd}
                onCancel={() => GoaState.empty('active-modal')}
            />
        });
    }

    render() {
        return (
            <React.Fragment>
                <div style={STYLES.actionsContainer}>
                    <ActionPanel label='Schedule a pickup' icon='fa fa-truck' onClick={this.handleShowAdd} />
                </div>
                <Spacer space='20px' />
                <PanelSearchTable
                    ref={e => this.table = e}
                    properties={PROPERTIES}
                    tableKey='Pickups'
                    tableTitle='Pickups'
                    tableIcon='fa fa-truck'
                    searchMethod={GoaApi.Pickup.search}
                    searchArgs={{ include_classes: 'address', external_user_id: 'NULL' }}
                    onAdd={this.handleShowAdd}
                    onSelectModel={this.handleSelect}
                />
            </React.Fragment>
        )
    }
}

const STYLES = {
    actionsContainer: {
        display: 'flex',
    }
}

