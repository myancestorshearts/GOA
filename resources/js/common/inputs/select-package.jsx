import SelectModel from './select-model';
import Select from './select';
import Text from './text';
import React from 'react';

import GoaEvent from '../goa-event';

import FlexContainer from '../components/flex-container';

import Functions from '../functions';


const PACKAGE_TYPES = [
    {
        label: 'Parcel',
        value: 'Parcel',
    },
    {
        label: 'Soft Pack',
        value: 'SoftPack'
    },
    {
        label: 'USPS - Flat Rate Padded Envelope',
        value: 'UspsFlatRatePaddedEnvelope'
    },
    {
        label: 'USPS - Flat Rate Legal Envelope',
        value: 'UspsFlatRateLegalEnvelope'
    },
    {
        label: 'USPS - Small Flat Rate Envelope',
        value: 'UspsSmallFlatRateEnvelope'
    },
    {
        label: 'USPS - Flat Rate Envelope',
        value: 'UspsFlatRateEnvelope'
    },
    {
        label: 'USPS - Small Flat Rate Box',
        value: 'UspsSmallFlatRateBox'
    },
    {
        label: 'USPS - Medium Flat Rate Box (Top Loading)',
        value: 'UspsMediumFlatRateBoxTopLoading'
    },
    {
        label: 'USPS - Medium Flat Rate Box (Side Loading)',
        value: 'UspsMediumFlatRateBoxSideLoading'
    },
    {
        label: 'USPS - Large Flat Rate Box',
        value: 'UspsLargeFlatRateBox'
    },
    {
        label: 'Regional Rate Box A Top Loading',
        value: 'UspsRegionalRateBoxATopLoading'
    },
    {
        label: 'Regional Rate Box A Side Loading',
        value: 'UspsRegionalRateBoxASideLoading'
    },
    {
        label: 'Regional Rate Box B Top Loading',
        value: 'UspsRegionalRateBoxBTopLoading'
    },
    {
        label: 'Regional Rate Box B Side Loading',
        value: 'UspsRegionalRateBoxBSideLoading'
    }
]



const PACKAGE_DICTIONARY = {
    UspsFlatRatePaddedEnvelope: {
        length: 12.5,
        width: .5,
        height: 9.5,
    },
    UspsFlatRateLegalEnvelope: {
        length: 15,
        width: .5,
        height: 9.5,
    },
    UspsSmallFlatRateEnvelope: {
        length: 10,
        width: .5,
        height: 6,
    },
    UspsFlatRateEnvelope: {
        length: 12.5,
        width: .5,
        height: 9.5,
    },
    UspsSmallFlatRateBox: {
        length: 8.62,
        width: 5.37,
        height: 1.62,
    },
    UspsMediumFlatRateBoxTopLoading: {
        length: 11,
        width: 8.5,
        height: 5.5,
    },
    UspsMediumFlatRateBoxSideLoading: {
        length: 13.62,
        width: 11.87,
        height: 3.37,
    },
    UspsLargeFlatRateBox: {
        length: 12,
        width: 12,
        height: 5.5,
    },
    UspsRegionalRateBoxATopLoading: {
        length: 10,
        width: 7,
        height: 4.75
    },
    UspsRegionalRateBoxASideLoading: {
        length: 10.9,
        width: 2.75,
        height: 12.81
    },
    UspsRegionalRateBoxBTopLoading: {
        length: 12,
        width: 10.25,
        height: 5
    },
    UspsRegionalRateBoxBSideLoading: {
        length: 14.75,
        width: 2.82,
        height: 15.82
    }
}

export default class SelectPackage extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            loading: true,
            packages: [],
            package: undefined,
            disableInputs: false
        }

        this.handleLoadPackages = this.handleLoadPackages.bind(this);
        this.handleChange = this.handleChange.bind(this);
    }

    componentDidMount() {
        this.handleLoadPackages();

        this.eventKey = GoaEvent.subscribe('package-refresh', this.handleLoadPackages)
    }

    componentWillUnmount() {
        GoaEvent.unsubscribe('package-refresh', this.eventKey);
    }

    handleLoadPackages() {
        if (this.state.package != undefined) this.handleChange(undefined);
        this.setState({
            loading: true,
            packages: []
        }, () => {

            GoaApi.Package.search({ take: 1000, page: 1 }, success => {

                // add a custom package
                success.data.models.push({ id: 'CUSTOM', name: 'Custom', type: 'Parcel', length: '', width: '', height: '' });

                this.setState({
                    packages: success.data.models,
                    loading: false,
                })
            })
        });
    }

    handleChange(model) {
        this.setState({
            package: model
        })

        // if not valid package do not return rate
        if (model &&
            model.id == 'CUSTOM' &&
            (Functions.isEmpty(model.length) ||
                Functions.isEmpty(model.height) ||
                Functions.isEmpty(model.width))
        ) return;

        if (this.props.onChange) {
            let modelToReturn = { ...model }
            if (modelToReturn.id == 'CUSTOM') delete modelToReturn.id;
            this.props.onChange(modelToReturn);
        }
    }

    handleEdit(property, value) {
        this.state.package[property] = value;

        if (property == 'type') {
            let isDictionary = PACKAGE_DICTIONARY.hasOwnProperty(value);
            let dimensions = isDictionary ? PACKAGE_DICTIONARY[value] : { length: '', width: '', height: '' };
            this.state.package.length = dimensions.length;
            this.state.package.width = dimensions.width;
            this.state.package.height = dimensions.height;
            this.state.disableInputs = isDictionary;
        }

        this.handleChange(this.state.package);
    }

    render() {

        return (
            <div>

                {/* Package */}
                <SelectModel
                    placeholderLoading='Loading Packages'
                    placeholder='Select Package'
                    loading={this.state.loading}
                    title='Package'
                    models={this.state.packages}
                    value={this.state.package}
                    onChange={this.handleChange}
                    stylesselect={this.props.stylesselect}
                    color={this.props.color}
                    styleslabel={this.props.styleslabel}
                />

                {
                    this.state.package && this.state.package.id == 'CUSTOM' ?
                        <div>

                            <Select
                                autoFocus={true}
                                title='Type'
                                onChange={e => this.handleEdit('type', e.target.value)}
                                value={this.state.package.type}
                                stylescontainer={STYLES.input}
                                stylesselect={this.props.stylesselect}
                                options={PACKAGE_TYPES}
                                color={this.props.color}
                                styleslabel={this.props.styleslabel}
                            />

                            <FlexContainer gap='10px'>
                                <Text
                                    stylescontainer={STYLES.textContainer}
                                    onChange={(e) => this.handleEdit('length', e.target.value)}
                                    value={this.state.package.length}
                                    title='Length(in)'
                                    disabled={this.state.disableInputs}
                                    stylesinput={this.props.stylestext}
                                    styleslabel={{ ...this.props.styleslabel, ...STYLES.sizeLabels }}
                                    color={this.props.color}
                                />
                                <Text
                                    stylescontainer={STYLES.textContainer}
                                    onChange={(e) => this.handleEdit('width', e.target.value)}
                                    value={this.state.package.width}
                                    title='Width(in)'
                                    disabled={this.state.disableInputs}
                                    stylesinput={this.props.stylestext}
                                    styleslabel={{ ...this.props.styleslabel, ...STYLES.sizeLabels }}
                                    color={this.props.color}
                                />
                                <Text
                                    stylescontainer={STYLES.textContainer}
                                    onChange={(e) => this.handleEdit('height', e.target.value)}
                                    value={this.state.package.height}
                                    title='Height(in)'
                                    disabled={this.state.disableInputs}
                                    stylesinput={this.props.stylestext}
                                    styleslabel={{ ...this.props.styleslabel, ...STYLES.sizeLabels }}
                                    color={this.props.color}
                                />
                            </FlexContainer>
                        </div> : null
                }


            </div>
        )
    }
}


const STYLES = {
    flexRow: {
        display: 'flex',
        alignItems: 'center'
    },
    textInput: {
        fontWeight: 'Bold',
        fontSize: '15px',
        color: '#555'
    },
    textContainer: {
        flex: 1,
        minWidth: '50px'
    },
    sizeLabels: {
        fontSize: '12px'
    }
}