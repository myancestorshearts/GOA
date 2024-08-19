import React from 'react';
import PanelSearchTable from '../../../common/portal/panel/search-table';
import GoaState from '../../../common/goa-state';

import ModelLabelCorrection from '../../../common/models/label-correction/model';

import FormLabelCorrectionsExport from './forms/export-label-corrections';

const PROPERTIES = {
    labeltracking: {
        title: 'Tracking',
        property: 'label,tracking',
        type: 'TEXT',
        default: true
    },
    amount: {
        title: 'Amount',
        property: 'amount',
        type: 'CURRENCY',
        default: true
    },
    service: {
        title: 'Corrected Service',
        property: 'service',
        type: 'TEXT',
        default: true
    },
    weight: {
        title: 'Corrected Weight',
        property: 'weight',
        type: 'WEIGHT',
        default: true
    },
    width: {
        title: 'Corrected Width',
        property: 'width',
        type: 'LENGTH',
        default: true
    },
    length: {
        title: 'Corrected Length',
        property: 'length',
        type: 'LENGTH',
        default: true
    },
    height: {
        title: 'Corrected Height',
        property: 'height',
        type: 'LENGTH',
        default: true
    },
    labelservice: {
        title: 'Entered Service',
        property: 'label,service',
        type: 'TEXT',
        default: true
    },
    labelweight: {
        title: 'Entered Weight',
        property: 'label,weight',
        type: 'WEIGHT',
        default: true
    },
    labelwidth: {
        title: 'Entered Width',
        property: 'label,shipment,package,width',
        type: 'LENGTH',
        default: true
    },
    labellength: {
        title: 'Entered Length',
        property: 'label,shipment,package,length',
        type: 'LENGTH',
        default: true
    },
    labelheight: {
        title: 'Entered Height',
        property: 'label,shipment,package,height',
        type: 'LENGTH',
        default: true
    },
    created: {
        title: 'Processed At',
        property: 'created_at',
        type: 'DATETIME',
        default: true
    },
    externaluserid: {
        title: 'External User',
        property: 'external_user_id',
        type: 'TEXT'
    }
};

export default class Orders extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            user: GoaUser.user
        }

        //this.handleSelectActive = this.handleSelectActive.bind(this);
        this.handleExport = this.handleExport.bind(this);
    }

    handleSelectActive(model) {
        GoaState.set('active-model', { model: model, component: <ModelLabelCorrection model={model} /> })
    }


    handleExport() {
        GoaState.set('active-modal', {
            component: <FormLabelCorrectionsExport
                onExport={() => GoaState.empty('active-modal')}
            />
        });
    }

    render() {
        return (
            <React.Fragment>
                <PanelSearchTable
                    ref={e => this.tableTransactionsCompleted = e}
                    properties={PROPERTIES}
                    onSelectModel={this.handleSelectActive}
                    onExport={this.handleExport}
                    tableKey='Corrections'
                    tableTitle='Corrections'
                    tableIcon='fa fa-industry'
                    searchMethod={GoaApi.LabelCorrection.search}
                    searchArgs={{
                        include_classes: 'label,shipment,package',
                    }}
                />
            </React.Fragment>
        )
    }
}
