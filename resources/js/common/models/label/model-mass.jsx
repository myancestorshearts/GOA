import React from 'react';
import GoaBrand from '../../brand';
import Button from '../../inputs/button';

import GoaApi from '../../api';

import ComponentLoading from '../../components/loading';


import Header from '../../fields/header';


import InputSelectDescriptions from '../../inputs/select-descriptions';

import Functions from '../../functions';


import Print4X6D1 from './print/4X6-1';
import Print85X11D2 from './print/85X11-2';
import Print85X11D4 from './print/85X11-4';

import ApiMass from '../../api-mass';


const OPTIONS_PRINT = [
    {
        title: '8.5x11” - 2 Shipping Labels per Page',
        description: 'No Packing Slips',
        value: '85X11-2',
        image: '/global/assets/images/print/options/85X11-2.png'
    },
    {
        title: '4x6” Shipping Label',
        description: 'No Packing Slips',
        value: '4X6-1',
        image: '/global/assets/images/print/options/4X6-1.png'
    },
    {
        title: '8.5x11” - 4 Shipping Labels per Page',
        description: 'No Packing Slips',
        value: '85X11-4',
        image: '/global/assets/images/print/options/85X11-4.png'
    },
    {
        title: '8.5x11” - 2 Shipping Labels per Pages',
        description: 'Includes Packing Slips',
        value: '85X11-2-PACK',
        image: '/global/assets/images/print/options/85X11-2.png'
    },
    {
        title: '4x6” Shipping Labels',
        description: 'Includes Packing Slips',
        value: '4X6-1-PACK',
        image: '/global/assets/images/print/options/4X6-1.png'
    },
    {
        title: '8.5x11” - 4 Shipping Labels per Page',
        description: 'Includes Packing Slips',
        value: '85X11-4-PACK',
        image: '/global/assets/images/print/options/85X11-4.png'
    }//,
    /*{
        title: '2x7” Shipping Label',
        description: 'Formatted for Thermal Label Printers',
        value: '2X7-1',
        image: '/global/assets/images/print/options/2X7-1.png'
    }*/
]

const PACKING_SLIPS = {
    '85X11-2': false,
    '4X6-1': false,
    '85X11-4': false,
    '85X11-2-PACK': true,
    '4X6-1-PACK': true,
    '85X11-4-PACK': true
}


const PRINT_COMPONENTS = {
    '85X11-2': Print85X11D2,
    '4X6-1': Print4X6D1,
    '85X11-4': Print85X11D4,
    '85X11-2-PACK': Print85X11D2,
    '4X6-1-PACK': Print4X6D1,
    '85X11-4-PACK': Print85X11D4
}

const PRINT_WIDTH = {
    '85X11-2': 11 * 200,
    '4X6-1': 4 * 200,
    '85X11-4': 8.5 * 200,
    '85X11-2-PACK': 11 * 200,
    '4X6-1-PACK': 4 * 200,
    '85X11-4-PACK': 8.5 * 200
}

const PRINT_HEIGHT = {
    '85X11-2': 8.5 * 200,
    '4X6-1': 6 * 200,
    '85X11-4': 11 * 200,
    '85X11-2-PACK': 8.5 * 200,
    '4X6-1-PACK': 6 * 200,
    '85X11-4-PACK': 11 * 200
}

const DEFAULT_PREFERENCE = '85X11-2';
const PREFERENCE_KEY = 'print_selection';

export default class ModelMass extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            printOption: DEFAULT_PREFERENCE,
            imageMap: {},
            packingSlipMap: {},
            loading: true
        }

        this.handlePrint = this.handlePrint.bind(this);
    }


    componentDidMount() {

        // get user preference for column that is sorted        
        GoaUser.getPreference(PREFERENCE_KEY,
            preference => {
                this.setState({
                    printOption: preference
                }, this.loadImages)
            },
            DEFAULT_PREFERENCE
        )
    }

    loadImages() {

        this.setState({
            loading: true,
            images: []
        }, () => {

            let imagesById = {};
            let packingSlipsById = {};

            let loadedCount = 0;

            let totalCount = this.props.labels.length;

            let finalizeImages = () => {

                this.setState({
                    imageMap: imagesById,
                    packingSlipMap: packingSlipsById,
                    loading: false
                }, () => {
                })

            }

            let apiMass = new ApiMass(5);

            this.props.labels.forEach((x) => {
                apiMass.push(GoaApi.Label.imageUrl, {
                    label_id: x.id
                }, success => {
                    imagesById[x.id] = 'data:image/png;base64,' + success.data.blob;
                    loadedCount++;
                    if (loadedCount == totalCount * 2) finalizeImages();
                }, () => {
                    loadedCount++;
                    if (loadedCount == totalCount * 2) finalizeImages();
                })
            });

            this.props.labels.forEach((x) => {
                apiMass.push(GoaApi.Label.packingSlipImageUrl, {
                    label_id: x.id
                }, success => {
                    packingSlipsById[x.id] = 'data:image/png;base64,' + success.data.blob;
                    loadedCount++;
                    if (loadedCount == totalCount * 2) finalizeImages();
                }, () => {
                    loadedCount++;
                    if (loadedCount == totalCount * 2) finalizeImages();
                })
            });

            apiMass.process();
        })
    }

    getImages() {
        let images = [];

        this.props.labels.forEach(x => {
            if (this.state.imageMap[x.id]) images.push(this.state.imageMap[x.id]);
            if (PACKING_SLIPS[this.state.printOption] && this.state.packingSlipMap[x.id]) images.push(this.state.packingSlipMap[x.id]);
        })

        return images;
    }

    handlePrint() {
        Functions.setPrintSize(PRINT_WIDTH[this.state.printOption], PRINT_HEIGHT[this.state.printOption]);
        let PrintComponent = PRINT_COMPONENTS[this.state.printOption];
        let images = this.getImages();
        Functions.printElement(Functions.reactRendorToObject(<PrintComponent images={images} width={PRINT_WIDTH[this.state.printOption]} />));
    }

    render() {
        let PrintComponent = PRINT_COMPONENTS[this.state.printOption];
        let images = this.getImages();
        return (
            <div>

                <Header title='Print Format' top={true} />

                <InputSelectDescriptions
                    stylescontainer={STYLES.inputDescriptionsContainer}
                    options={OPTIONS_PRINT}
                    value={this.state.printOption}
                    onChange={x => this.setState({ printOption: x }, () => {
                        GoaUser.setPreference(PREFERENCE_KEY, x)
                    })}
                />

                <Header title='Preview' />

                <ComponentLoading loading={this.state.loading}>
                    <div style={STYLES.imageContainer}>
                        <PrintComponent images={images} width={600} />
                    </div>
                </ComponentLoading>

                <div style={STYLES.labelButtons}>
                    <Button
                        props={{
                            onClick: this.handlePrint
                        }}
                        color={GoaBrand.getPrimaryColor()}
                        stylesbutton={STYLES.button}
                        stylesbuttonhover={STYLES.buttonHover}
                    >
                        Print
                    </Button>
                </div>

            </div>
        )
    }
}



/*
class LabelImage extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            labelUrl: undefined,
            loading: true
        };
    }

    componentDidMount() {
        GoaApi.Label.imageUrl({
            label_id: this.props.label.id
        }, success => {
            this.setState({
                labelUrl: success.data.url,
                loading: false
            })
        })
    }

    render() {
        return <ComponentLoading loading={this.state.loading}><img src={this.state.labelUrl} style={STYLES.labelImage} /></ComponentLoading>
    }
}*/

const STYLES = {
    inputDescriptionsContainer: {
        marginLeft: '-20px',
        marginRight: '-20px',
        marginBottom: '-5px'
    },
    imageContainer: {
        paddingTop: '20px',
        height: '530px',
        display: 'flex',
        flexDirection: 'column',
        overflow: 'auto',
        marginRight: '-20px',
        marginLeft: '-20px',
        width: '620px'
    },
    button: {
        width: '170px'
    },
    labelButtons: {
        display: 'flex'
    },
    labelImage: {
        maxWidth: '350px',
        marginBottom: '10px'
    },
    button: {
        height: '50px',
        borderRadius: '20px',
        color: 'white',
        backgroundColor: GoaBrand.getPrimaryColor()
    },
    buttonHover: {
        backgroundColor: GoaBrand.getPrimaryHoverColor()
    }
}
