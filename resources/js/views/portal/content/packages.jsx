import React from 'react';
import Panel from '../../../common/portal/panel';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';
import toastr from 'toastr';

import GoaState from '../../../common/goa-state';
import GoaEvent from '../../../common/goa-event';

import FormAddPackage from './forms/add-package';
import GoaBrand from '../../../common/brand';

import {TourMethods} from 'react-shepherd'


const PROPERTIES = {

    name: {
        title: 'Name',
        property: 'name',
        type: 'TEXT',
        default: 'true'
    },
    type: {
        title: 'Type',
        property: 'type',
        type: 'TEXT',
        default: 'true'
    },
    length: {
        title: 'length (in)',
        property: 'length',
        type: 'TEXT',
        default: 'true'
    },
    width: {
        title: 'width (in)',
        property: 'width',
        type: 'TEXT',
        default: 'true'
    },
    height: {
        title: 'height (in)',
        property: 'height',
        type: 'TEXT',
        default: 'true'
    }
}

export default class Packages extends React.Component {
    
    constructor(props) {
        super(props)
        this.state = {
        }

        this.handleAdd = this.handleAdd.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
        this.handleAddOpen = this.handleAddOpen.bind(this);
        this.handleCancel = this.handleCancel.bind(this);
    }


    handleAdd(tour) {
        if (this.table) this.table.handleSearch();
        GoaState.empty('active-modal')
        GoaEvent.trigger('package-refresh');
        if(tour.isActive()){
            tour.next();
        }
    }

    handleAddOpen(tour) {
        GoaState.set('active-modal', {
            component: <FormAddPackage 
                onAdd={() => this.handleAdd(tour)}
                onCancel={() => this.handleCancel(tour)}
                tour={tour}
            />, className: 'addPackage'
        })
    }

    handleDelete(model) {
        GoaApi.Package.deactivate({id: model.id}, () => {
            GoaEvent.trigger('package-refresh');
            if (this.table) this.table.handleSearch();
        }, failure => {
            toastr.error(failure.message)
        })
    }

    handleCancel(tour) {
        GoaState.empty('active-modal')
        if(tour.isActive()){
            tour.next()
        }
    }
    
    render() {
        return (

            <TourMethods>
                {(tourContext) => (
                    <React.Fragment>
                        <div style={STYLES.actionsContainer} className={'packageCreate'}> 
                            <ActionPanel label='Create a saved package' icon='fa fa-cube' onClick={() => this.handleAddOpen(tourContext)}/>
                        </div>

                        <Spacer space='20px'/>
                        <PanelSearchTable
                            ref={e => this.table = e}
                            properties={PROPERTIES}
                            tableKey='PackagesSaved'
                            tableTitle='Saved Packages'
                            tableIcon='fa fa-cubes'
                            searchMethod={GoaApi.Package.search}
                            onDelete={this.handleDelete}
                            onAdd={() => this.handleAddOpen(tourContext)}
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
        border: '1px solid' + GoaBrand.getPrimaryColor(),
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

