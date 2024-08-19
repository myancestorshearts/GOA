


import React from 'react';

import Spacer from '../../../../common/components/spacer';
import Button from '../../../../common/inputs/button';
import Header from '../../../../common/fields/header';
import SelectModel from '../../../../common/inputs/select-model';
import InputSelect from '../../../../common/inputs/select';
import InputDateTime from '../../../../common/inputs/datetime';
import GoaBrand from '../../../../common/brand';

import Functions from '../../../../common/functions';
import FlexContainer from '../../../../common/components/flex-container';
import ComponentLoading from '../../../../common/components/loading';
import toastr from 'toastr';

const LOCATION_OPTIONS = [
    {
        value: 'FRONT_DOOR',
        label: 'Front Door'
    },
    {
        value: 'BACK_DOOR',
        label: 'Back Door'
    },
    {
        value: 'SIDE_DOOR',
        label: 'Side Door'
    },
    {
        value: 'KNOCK_ON_DOOR',
        label: 'Knock On Door'
    },
    {
        value: 'MAIL_ROOM',
        label: 'Mail Room'
    },
    {
        value: 'OFFICE',
        label: 'Office'
    },
    {
        value: 'RECEPTION',
        label: 'Reception'
    },
    {
        value: 'IN_MAILBOX',
        label: 'In Mailbox'
    },
    {
        value: 'OTHER',
        label: 'Other'
    }
]

export default class AddScanForm extends React.Component {

    constructor(props) {
        super(props);

        let defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + 1);

        this.state = {
            fromAddress: undefined,
            selectedLabelIds: [],
            selectedScanFormIds: [],
            addresses: [],
            labels: [],
            scanForms: [],
            shipDate: undefined,
            dateOptions: [],
            selectedDate: defaultDate,
            loading: true,
            loadingAvailability: false,
            nextAvailableOption: undefined,
            location: 'FRONT_DOOR'
        };

        this.handleLoadScanAndLabels = this.handleLoadScanAndLabels.bind(this);
        this.handleSelectAllLabels = this.handleSelectAllLabels.bind(this);
        this.handleSelectAllScanForms = this.handleSelectAllScanForms.bind(this);
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleAddressSelect = this.handleAddressSelect.bind(this);
        this.handleAvailabilitySearch = this.handleAvailabilitySearch.bind(this);
    }

    componentDidMount() {
        this.handleLoadAddresses();
    }

    handleLoadAddresses() {
        GoaApi.Pickup.addresses({
        }, success => {
            this.setState({
                addresses: success.data.models,
                loading: false
            })
        })
    }

    handleLoadScanAndLabels() {
        GoaApi.Label.search({
            from_address_id: this.state.fromAddress.id,
            scan_form_id: 'NULL',
            pickup_id: 'NULL',
            take: 1000,
            page: 1,
            external_user_id: 'NULL'
        }, success => {
            this.setState({ labels: success.data.models })
        })

        GoaApi.ScanForm.search({
            from_address_id: this.state.fromAddress.id,
            pickup_id: 'NULL',
            take: 1000,
            page: 1,
            external_user_id: 'NULL'
        }, success => {
            this.setState({ scanForms: success.data.models })
        })
    }

    handleSelectAllLabels() {
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

    handleSelectAllScanForms() {
        if (this.state.selectedScanFormIds.length == this.state.scanForms.length) {
            this.setState({
                selectedScanFormIds: []
            })
        }
        else {
            let selectedScanFormIds = this.state.scanForms.map(x => x.id);
            this.setState({
                selectedScanFormIds: selectedScanFormIds
            })
        }
    }

    handleToggleLabel(model) {
        if (this.state.selectedLabelIds.includes(model.id)) {
            this.state.selectedLabelIds = this.state.selectedLabelIds.filter(x => x != model.id)
        }
        else this.state.selectedLabelIds.push(model.id);

        this.forceUpdate();
    }

    handleToggleScanForm(model) {
        if (this.state.selectedScanFormIds.includes(model.id)) {
            this.state.selectedScanFormIds = this.state.selectedScanFormIds.filter(x => x != model.id)
        }
        else this.state.selectedScanFormIds.push(model.id);

        this.forceUpdate();
    }

    handleSubmit(e) {
        if (e) e.preventDefault();

        GoaApi.Pickup.schedule({
            from_address_id: this.state.fromAddress.id,
            label_ids: this.state.selectedLabelIds,
            scan_form_ids: this.state.selectedScanFormIds,
            package_location: this.state.location,
            date: this.state.selectedDate
        }, success => {
            this.props.onAdd(success.data.model);
        }, failure => {
            toastr.error(failure.message);
        })
    }

    handleAddressSelect(address) {
        this.setState({
            fromAddress: address
        }, this.handleAvailabilitySearch)
    }

    handleAvailabilitySearch() {
        if (!this.state.fromAddress) return;
        this.setState({
            loadingAvailability: true,
            labels: [],
            scanForms: [],
            selectedLabelIds: [],
            selectedScanFormIds: []
        }, () => {
            GoaApi.Pickup.availability({
                date: Functions.convertDateToMysql(this.state.selectedDate),
                from_address_id: this.state.fromAddress.id
            }, success => {
                this.setState({
                    nextAvailableOption: success.data.model,
                    loadingAvailability: false
                }, this.handleLoadScanAndLabels)
            }, failure => {
                this.setState({
                    loadingAvailability: false
                })
                toastr.error(failure.message);
            })
        })
    }

    render() {

        let labels = this.state.labels.map(x => {
            return (
                <LabelItem
                    key={x.id}
                    label={x}
                    checked={this.state.selectedLabelIds.includes(x.id)}
                    onToggle={() => this.handleToggleLabel(x)}
                />
            )
        })

        let scanForms = this.state.scanForms.map(x => {
            return (
                <ScanFormItem
                    key={x.id}
                    scanForm={x}
                    checked={this.state.selectedScanFormIds.includes(x.id)}
                    onToggle={() => this.handleToggleScanForm(x)}
                />
            )
        })

        let allSelectedLabels = this.state.selectedLabelIds.length == this.state.labels.length;
        let allSelectedScanForms = this.state.selectedScanFormIds.length == this.state.scanForms.length;

        return (
            <React.Fragment>
                <Header title='Schedule A Pickup' top={true} />
                <Spacer space='20px' />
                <ComponentLoading loading={this.state.loading}>
                    {
                        Object.keys(this.state.addresses).length > 0 ?
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
                                <InputSelect
                                    title='Location'
                                    onChange={e => this.setState({ location: e.target.value })}
                                    value={this.state.location}
                                    options={LOCATION_OPTIONS}
                                    stylesselect={STYLES.selectInput}
                                    styleslabel={STYLES.label}
                                />
                                <InputDateTime
                                    title='Dates'
                                    onChange={value => this.setState({ selectedDate: value }, this.handleAvailabilitySearch)}
                                    value={this.state.selectedDate}
                                    options={this.state.dateOptions}
                                    stylesinput={STYLES.textInput}
                                    styleslabel={STYLES.label}
                                />

                                <ComponentLoading loading={this.state.loadingAvailability}>
                                    <React.Fragment>
                                        {
                                            this.state.nextAvailableOption ?
                                                <React.Fragment>
                                                    <div style={STYLES.nextDateDisclosure}>The next available date is {Functions.convertMysqlToDateRaw(this.state.nextAvailableOption.date).formatDate('n/d/Y')}.  Select scan forms and labels to continue scheduling a pickup.</div>


                                                    {this.state.scanForms.length > 0 ?
                                                        <React.Fragment>
                                                            <Spacer space='20px' />
                                                            <Header title='Scan Forms' />
                                                            <Spacer space='20px' />

                                                            <FlexContainer>
                                                                <div>
                                                                    <input
                                                                        style={STYLES.labelCheck}
                                                                        checked={allSelectedScanForms}
                                                                        type='checkbox'
                                                                        onChange={this.handleSelectAllScanForms}
                                                                    />
                                                                </div>
                                                                <div>Select All</div>
                                                            </FlexContainer>
                                                            {scanForms}
                                                        </React.Fragment> : null
                                                    }


                                                    {this.state.labels.length > 0 ?
                                                        <React.Fragment>
                                                            <Spacer space='20px' />
                                                            <Header title='Label' />
                                                            <Spacer space='20px' />
                                                            <FlexContainer>
                                                                <div>
                                                                    <input
                                                                        style={STYLES.labelCheck}
                                                                        checked={allSelectedLabels}
                                                                        type='checkbox'
                                                                        onChange={this.handleSelectAllLabels}
                                                                    />
                                                                </div>
                                                                <div>Select All</div>
                                                            </FlexContainer>
                                                            {labels}
                                                        </React.Fragment> : null
                                                    }
                                                </React.Fragment>
                                                : null
                                        }
                                    </React.Fragment>
                                </ComponentLoading>


                                <Button
                                    props={{ type: 'submit' }}
                                    color={GoaBrand.getPrimaryColor()}
                                    stylesbutton={STYLES.button}
                                >
                                    Schedule Pickup
                                </Button>
                            </form> : <div>
                                No Labels Available For Pickup
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
                    onChange={props.onToggle}
                    checked={props.checked}
                />
            </div>
            <div>{props.label.tracking}</div>
        </FlexContainer>
    )
}

const ScanFormItem = (props) => {
    return (
        <FlexContainer>
            <div>
                <input
                    style={STYLES.labelCheck}
                    type='checkbox'
                    onChange={props.onToggle}
                    checked={props.checked}
                />
            </div>
            <div>{props.scanForm.ship_date} - {props.scanForm.label_count} Labels</div>
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
    selectInput: {
        fontWeight: '600',
        fontFamily: 'poppins',
        fontSize: '18px',
        color: '#273240',
        borderRadius: '20px',
        height: '50px',
        borderColor: '#96A0AF'
    },
    textInput: {
        fontWeight: '600',
        fontFamily: 'poppins',
        fontSize: '18px',
        color: '#273240',
        borderRadius: '20px',
        height: '44px',
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
    },
    nextDateDisclosure: {
        fontFamily: 'poppins',
        fontSize: '18px',
        maxWidth: '600px'
    }
}