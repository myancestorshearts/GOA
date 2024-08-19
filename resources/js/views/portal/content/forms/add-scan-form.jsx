


import React from 'react';

import Spacer from '../../../../common/components/spacer';
import Button from '../../../../common/inputs/button';
import Header from '../../../../common/fields/header';
import SelectModel from '../../../../common/inputs/select-model';
import Select from '../../../../common/inputs/select';
import GoaBrand from '../../../../common/brand';

import Functions from '../../../../common/functions';
import FlexContainer from '../../../../common/components/flex-container';
import ComponentLoading from '../../../../common/components/loading';
import toastr from 'toastr';

export default class AddScanForm extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            fromAddress: undefined,
            selectedLabelIds: [],
            addresses: [],
            labels: [],
            shipDate: undefined,
            dateOptions: [],
            options: {},
            selectedDate: undefined,
            loading: true
        };

        this.handleLabelSearch = this.handleLabelSearch.bind(this);
        this.handleSelectAll = this.handleSelectAll.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleAddressSelect = this.handleAddressSelect.bind(this);
    }

    componentDidMount() {
        this.handleLoadAddresses();
    }

    handleLoadAddresses() {
        GoaApi.ScanForm.options({}, success => {
            this.setState({
                addresses: success.data.addresses,
                options: success.data.models,
                loading: false
            })
        })
    }

    handleLabelSearch() {
        GoaApi.Label.search({
            from_address_id: this.state.fromAddress.id,
            scan_form_id: 'NULL',
            label_ids: this.state.options[this.state.fromAddress.id][this.state.selectedDate],
            take: 1000,
            page: 1
        }, success => {
            this.setState({ labels: success.data.models })
        })
    }

    handleSelectAll() {
        if (this.state.selectedLabelIds.length == this.state.labels.length) {
            this.setState({
                selectedLabelIds: []
            })
        }
        else {
            let selectedLabelIds = this.state.labels.map(x => x.id);
            this.setState({
                selectedLabelIds: selectedLabelIds
            })
        }
    }

    handleToggle(model) {
        if (this.state.selectedLabelIds.includes(model.id)) {
            this.state.selectedLabelIds = this.state.selectedLabelIds.filter(x => x != model.id)
        }
        else this.state.selectedLabelIds.push(model.id);

        this.forceUpdate();
    }

    handleSubmit(e) {
        if (e) e.preventDefault();

        GoaApi.ScanForm.add({
            from_address_id: this.state.fromAddress.id,
            label_ids: this.state.selectedLabelIds,
            ship_date: this.state.selectedDate
        }, success => {
            this.props.onAdd(success.data.model);
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleAddressSelect(address) {
        this.setState({
            fromAddress: address,
            dateOptions: Object.keys(this.state.options[address.id]).map(x => ({ value: x, label: x })),
            selectedDate: Object.keys(this.state.options[address.id])[0]
        }, this.handleLabelSearch)
    }

    render() {

        let labels = this.state.labels.map(x => {
            return (
                <LabelItem
                    key={x.id}
                    label={x}
                    checked={this.state.selectedLabelIds.includes(x.id)}
                    handleToggle={() => this.handleToggle(x)}
                />
            )
        })

        let allSelected = this.state.selectedLabelIds.length == this.state.labels.length;

        return (
            <React.Fragment>
                <Header title='Add Scan Form' top={true} />
                <Spacer space='20px' />
                <ComponentLoading loading={this.state.loading}>
                    {
                        Object.keys(this.state.options).length > 0 ?
                            <form onSubmit={this.handleSubmit}>
                                <SelectModel
                                    autoFocus={true}
                                    title='From Address'
                                    onChange={this.handleAddressSelect}
                                    value={this.state.fromAddress}
                                    models={this.state.addresses}
                                    methodLabel={x => x.name + ' - ' + Functions.formatAddress(x)}
                                    stylesselect={STYLES.selectInput}
                                    styleslabel={STYLES.label}
                                />
                                {
                                    this.state.dateOptions.length > 0 ?
                                        <Select
                                            title='Dates'
                                            onChange={e => this.setState({ selectedDate: e.target.value }, this.handleLabelSearch)}
                                            value={this.state.selectedDate}
                                            options={this.state.dateOptions}
                                            stylesselect={STYLES.selectInput}
                                            styleslabel={STYLES.label}
                                        />
                                        : null
                                }
                                {
                                    this.state.labels.length > 0 ?
                                        <FlexContainer>
                                            <div>
                                                <input
                                                    style={STYLES.labelCheck}
                                                    checked={allSelected}
                                                    type='checkbox'
                                                    onChange={this.handleSelectAll}
                                                />
                                            </div>
                                            <div>Select All</div>
                                        </FlexContainer> : null
                                }
                                {labels}
                                <Button
                                    props={{ type: 'submit' }}
                                    color={GoaBrand.getPrimaryColor()}
                                    stylesbutton={STYLES.button}
                                >
                                    Add Scan Form
                                </Button>
                            </form> : <div>
                                No Labels Available For Scan Form
                            </div>
                    }
                </ComponentLoading>
            </React.Fragment>
        )
    }
}

const LabelItem = (props) => {
    return (
        <FlexContainer>
            <div>
                <input
                    style={STYLES.labelCheck}
                    type='checkbox'
                    onChange={props.handleToggle}
                    checked={props.checked}
                />
            </div>
            <div>{props.label.tracking}</div>
        </FlexContainer>
    )
}


const STYLES = {
    labelCheck: {
        height: '20px',
        width: '20px',
        marginLeft: '5px',
        marginRight: '-10px'
    },
    flexRow: {
        display: 'flex',
        alignItems: 'center'
    },
    selectInput: {
        fontWeight: '600',
        fontFamily: 'poppins',
        fontSize: '18px',
        color: '#273240',
        borderRadius: '20px',
        height: '50px',
        borderColor: '#96A0AF'
    },
    label: {
        fontFamily: 'poppins',
        fontWeight: '600',
        fontSize: '12px',
        color: '#96A0AF'
    },
    button: {
        height: '50px',
        borderRadius: '20px',
        color: 'white',
        backgroundColor: GoaBrand.getPrimaryColor()
    }
}