import React from 'react';

import Panel from './index';
import Spacer from '../../components/spacer';
import GoaBrand from '../../brand';

const ActionPanel = (props) => {
    return (
        <Panel 
            style={STYLES.dashboardActionPanel} 
            styleHover={STYLES.dashboardActionPanelHover}
            onClick={props.onClick}
        >
            <i style={STYLES.dashboardActionIcon} className={props.icon}></i>
            <Spacer space='20px'/>
            {props.label}
        </Panel>
    )
}
const STYLES = {
    
    dashboardActionPanel: {
        minWidth: '150px',
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
    dashboardActionIcon: {
        color: GoaBrand.getPrimaryColor(),
        fontSize: '50px'
    },
    dashboardActionPanelHover: {
        border: '1px solid ' + GoaBrand.getPrimaryColor(),
        backgroundColor: '#eee'
    },
}
export default ActionPanel;