import React from 'react';
import GoaBrand from '../../brand';
import Spacer from '../../components/spacer';
import Button from '../../inputs/button';

import ReactToPrint from 'react-to-print';

import GoaApi from '../../api';




export default class Model extends React.Component {
    
    constructor(props) {
        super(props);
        this.state = {
            labelUrl: undefined
        };

        this.handlePrint = this.handlePrint.bind(this);
    }

    componentDidMount() {
        GoaApi.Label.imageUrl({
            label_id: this.props.label.id
        }, success => {
            this.setState({
                labelUrl: success.data.url
            })
        })
    }

    handlePrint() {
        useReactToPrint({
            content: () => this.labelImage,
        });
    }

    render() {
        if (!this.state.labelUrl) return null;
    
        return (
            <React.Fragment>
                <LabelImage 
                    ref={e => this.labelImage = e}
                    url={this.state.labelUrl} 
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
                    <Spacer space='10px'/>
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
        return <img src={this.props.url} style={STYLES.labelImage}/>
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
