
import React from 'react';

import GoaState from '../goa-state';
import GoaBrand from '../brand';

import Panel from './panel';

export default class ActiveView extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            component: undefined
        }


        this.handleClose = this.handleClose.bind(this);
    }

    componentDidMount() {
        GoaState.subscribe(this.props.stateKey, (state) => {
            this.setState({ component: state.component })
        })
    }

    handleClose() {
        GoaState.set(this.props.stateKey, { component: undefined, model: undefined })
    }

    render() {
        if (!this.state.component) return null;

        return (
            <div style={STYLES.container}>
                <div style={STYLES.subContainer}>
                    <Panel style={STYLES.panel}>
                        {this.state.component}
                    </Panel>
                    <i style={STYLES.icon} className='fa fa-times' onClick={this.handleClose} />
                </div>
            </div>
        )
    }
}

const STYLES = {
    container: {
        display: 'flex',
        width: '395px',
        alignItems: 'center',
        zIndex: '1',
        backgroundColor: GoaBrand.getBackgroundColor()
    },
    subContainer: {
        height: 'calc(100% - 100px)',
        width: '100%',
        maxHeight: '100%',
        overflowY: 'overlay',
        overflowX: 'hidden',
        marginRight: '-20px',
        paddingRight: '20px',
        paddingBottom: '100px',
        position: 'relative'
    },
    panel: {
        marginBottom: '20px',
        width: 'calc(100% - 60px)'
    },
    icon: {
        cursor: 'pointer',
        position: 'absolute',
        top: '17px',
        right: '60px'
    }
}
