import React from 'react';
import Panel from '../../../common/portal/panel';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';
import toastr from 'toastr';
import { useContext } from 'react';
import Modal from '../../../common/portal/modal';

import GoaState from '../../../common/goa-state';
import GoaEvent from '../../../common/goa-event';

import FormAddAddress from './forms/add-address';

import Functions from '../../../common/functions';

import GoaBrand from '../../../common/brand';

import {TourMethods} from 'react-shepherd'

import AddressModel from '../../../common/models/address/model';

const PROPERTIES = {

    name: {
        title: 'Name',
        property: 'name',
        type: 'TEXT',
        default: true
    },
    company: {
        title: 'Company',
        property: 'company',
        type: 'TEXT',
        default: true
    },
    email: {
        title: 'Email',
        property: 'email',
        type: 'TEXT',
        default: true
    },
    phone: {
        title: 'Phone',
        property: 'phone',
        type: 'TEXT',
        default: true
    },
    formatted: {
        title: 'Address',
        type: 'METHOD',
        method: model => {
            let formatted = model.street_1;
            if (!Functions.isEmpty(model.street_2)) formatted += ' ' + model.street_2;
            formatted += ', ' + model.city + ', ' + model.state + ' ' + model.postal;
            return formatted;
        },
        default: true
    },
    default: {
        title: 'Default',
        property: 'default',
        type: 'BOOLEAN',
        boolTrue: 'Default',
        boolFalse: '',
        default: true
    }
}

export default class Addresses extends React.Component {
    
    constructor(props) {
        super(props)
        this.state = {
        }

        this.handleAdd = this.handleAdd.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
        this.handleOpen = this.handleOpen.bind(this);
        this.handleCancel = this.handleCancel.bind(this);
        this.handleSelectModel = this.handleSelectModel.bind(this);
    }


    handleAdd(tour) {
        if (this.table) this.table.handleSearch();
        GoaState.empty('active-modal')
        GoaEvent.trigger('address-refresh');
        if(tour.isActive()){
            tour.next()
        }
    }


    handleDelete(model) {
        GoaApi.Address.deactivate({id: model.id}, () => {
            if (this.table) this.table.handleSearch();
            GoaEvent.trigger('address-refresh');
        }, failure => {
            toastr.error(failure.message)
        })
    }

    handleOpen(tour) {
        GoaState.set('active-modal', {
            component: <FormAddAddress
                onAdd={() => this.handleAdd(tour)}
                onCancel={() => this.handleCancel(tour)}
                tour={tour}
            />
            , className: 'addAddress'
        });
    }

    handleCancel(tour) {
        GoaState.empty('active-modal')
        if(tour.isActive()){
            tour.next()
        }
    }

    handleSelectModel(model) {
        GoaState.set('active-model', {model: model, component: <AddressModel 
            model={model}
            onSave={() => {
                if (this.table) this.table.handleSearch();
            }}
        />})
    }


    render() {
        return (
            
            <TourMethods>
            {(tourContext) => (
                <React.Fragment>
                <div style={STYLES.actionsContainer} className={'addressCreate'}> 
                    <ActionPanel label='Create a saved address' icon='fa fa-map-marker' onClick={() => this.handleOpen(tourContext)} />
                </div>

                <Spacer space='20px'/>
                <PanelSearchTable
                    ref={e => this.table = e}
                    properties={PROPERTIES}
                    tableKey='AddressesSaved'
                    tableTitle='Saved Addresses'
                    tableIcon='fa fa-map'
                    searchMethod={GoaApi.Address.search}
                    onDelete={this.handleDelete}
                    onSelectModel={this.handleSelectModel}
                />
                </React.Fragment>
            )}
            </TourMethods>
        )
    }
}
const ActionPanel = (props) => {


    return (
        <Panel 
            style={STYLES.actionPanel} 
            styleHover={STYLES.actionPanelHover}
            onClick={() => props.onClick()}
        >
            <i style={STYLES.actionIcon} className={props.icon}></i>
            <Spacer space='20px'/>
            {props.label}
        </Panel>
    )
}


const STYLES = {
    actionsContainer: {
        display: 'flex',
    },
    actionPanel: {
        flex: 1,
        border: '1px solid white',
        cursor: 'pointer',
        transition: 'all .2s ease-in-out',
        backgroundColor: 'white',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        fontWeight: 'bold',
        textAlign: 'center',
        justifyContent: 'center'
    },
    actionIcon: {
        color: GoaBrand.getPrimaryColor(),
        fontSize: '50px'
    },
    actionPanelHover: {
        border: '1px solid '+ GoaBrand.getPrimaryColor(),
        backgroundColor: '#eee'
    },
    button: {
        marginLeft: '10px'
    },
    buttonsContainer:{
        marginLeft: 'auto',
        display: 'flex'
    }
}

