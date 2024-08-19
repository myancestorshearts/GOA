import React from 'react';

import Panel from './index';
import Spacer from '../../components/spacer';
import GoaBrand from '../../brand';

const IntegrationPanel = (props) => {
    return (
        <Panel 
            style={STYLES.panel} 
            styleHover={STYLES.panelHover}
            onClick={props.onClick}
        >
            <img style={STYLES.image} src={props.image} />
            <Spacer space='20px'/>
            {props.label}   
        </Panel>
    )
}
const STYLES = {
    panel: {
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
        justifyContent: 'center',
        minWidth: '200px',
        maxWidth: '300px'
    },
    image: {
        width: '100%',
        borderRadius: '10px'
    },
    panelHover: {
        border: '1px solid ' + GoaBrand.getPrimaryColor(),
        backgroundColor: '#eee'
    }
}
export default IntegrationPanel;