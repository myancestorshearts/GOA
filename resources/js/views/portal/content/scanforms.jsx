import React from 'react';
import Panel from '../../../common/portal/panel';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';
import toastr from 'toastr';

import GoaState from '../../../common/goa-state';

import FormAddAddress from './forms/add-address';

import Functions from '../../../common/functions';

import ActionPanel from '../../../common/portal/panel/action';

import FormAddScanForm from './forms/add-scan-form';
import ScanFormModel from '../../../common/models/scanform/model';


const PROPERTIES = {
    barcode: {
        title: 'Barcode',
        property: 'barcode',
        type: 'TEXT',
        default: 'true'
    },
    labelcount: {
        title: 'Label Count',
        property: 'label_count',
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

export default class ScanForms extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            showAdd: false
        }

        this.handleAdd = this.handleAdd.bind(this);
        this.handleShowAdd = this.handleShowAdd.bind(this);
        this.handleSelect = this.handleSelect.bind(this);
    }


    handleAdd(model) {
        if (this.table) this.table.handleSearch();
        this.handleSelect(model)
    }

    handleSelect(model) {
        GoaState.set('active-modal', {
            component: <ScanFormModel
                model={model}
                onClose={() => GoaState.empty('active-modal')}
            />
        })
    }

    handleShowAdd() {
        GoaState.set('active-modal', {
            component: <FormAddScanForm
                onAdd={this.handleAdd}
                onCancel={() => GoaState.empty('active-modal')}
            />
        });
    }

    render() {
        return (
            <React.Fragment>
                <div style={STYLES.actionsContainer}>
                    <ActionPanel label='Create a scan form' icon='fa fa-barcode' onClick={this.handleShowAdd} />
                </div>
                <Spacer space='20px' />
                <PanelSearchTable
                    ref={e => this.table = e}
                    properties={PROPERTIES}
                    tableKey='ScanForms'
                    tableTitle='Scan Forms'
                    tableIcon='fa fa-barcode'
                    searchMethod={GoaApi.ScanForm.search}
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

