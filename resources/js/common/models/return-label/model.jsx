import React from 'react';
import GoaBrand from '../../brand';
import Spacer from '../../components/spacer';
import Button from '../../inputs/button';

import ReactToPrint from 'react-to-print';

import GoaApi from '../../api';




export default class Model extends React.Component {

    constructor(props) {
        super(props);
        this.handlePrint = this.handlePrint.bind(this);
    }


    handlePrint() {
        useReactToPrint({
            content: () => this.labelImage,
        });
    }

    render() {

        return (
            <React.Fragment>
                <LabelImage
                    ref={e => this.labelImage = e}
                    url={this.props.returnLabel.url}
                />
                <div style={STYLES.labelButtons}>
                    <ReactToPrint
                        ref={e => this.reactToPrint = e}
                        trigger={
                            () => <Button
                                stylesbutton={STYLES.button}
                                props={{
                                    type: 'button',
                                    onClick: () => {
                                        this.reactToPrint.handlePrint()
                                    }
                                }}
                                color={GoaBrand.getPrimaryColor()}
                            >
                                Print Label
                            </Button>
                        }
                        content={() => this.labelImage}
                    />
                    <Spacer space='10px' />
                    <Button
                        stylesbutton={STYLES.button}
                        props={{
                            type: 'button',
                            onClick: this.props.onClose
                        }}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Close
                    </Button>
                </div>
            </React.Fragment>
        )
    }
}



class LabelImage extends React.Component {
    render() {
        return <img src={this.props.url} style={STYLES.labelImage} />
    }
}

const STYLES = {
    button: {
        width: '170px'
    },
    labelButtons: {
        display: 'flex'
    },
    labelImage: {
        maxWidth: '350px'
    }
}
