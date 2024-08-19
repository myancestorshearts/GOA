import React from 'react';
import GoaBrand from '../../brand';
import Spacer from '../../components/spacer';
import Button from '../../inputs/button';

import { useReactToPrint } from 'react-to-print';




const Model = (props) => {

    let scanFormImage = undefined;

    let handlePrint = useReactToPrint({
        content: () => scanFormImage,
    });

    return (
        <React.Fragment>
            <ScanFormImage
                ref={e => scanFormImage = e}
                url={props.model.url}
            />
            <div style={STYLES.scanformButtons}>
                <Button
                    props={{ onClick: handlePrint }}
                    color={GoaBrand.getPrimaryColor()}
                >
                    Print Form
                </Button>
                <Spacer space='10px' />
                <Button
                    props={{
                        type: 'button',
                        onClick: props.onClose
                    }}
                    color={GoaBrand.getPrimaryColor()}
                >
                    Close
                </Button>
            </div>
        </React.Fragment>
    )
}

class ScanFormImage extends React.Component {
    render() {
        return <img src={this.props.url} style={STYLES.scanFormImage} />
    }
}

const STYLES = {
    scanformButtons: {
        display: 'flex'
    },
    scanFormImage: {
        maxWidth: '600px'
    }
}

export default Model;