import React from 'react';
import StyledComponent from '../../components/styled-component';
import ActiveLabel from './model';
import GoaBrand from '../../brand';
import Modal from '../../portal/modal';
import GoaState from '../../goa-state';


export default class TrackingLink extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            active: false
        }


        this.handleOpen = this.handleOpen.bind(this);
    }

    handleOpen() {
        GoaState.set('active-modal', {
            component: <ActiveLabel
                label={this.props.label}
                onClose={() => GoaState.empty('active-modal')}
            />
        });
    }

    render() {
        return (
            <div>
                <StyledComponent 
                    style={STYLES.trackingLink} 
                    styleHover={STYLES.trackingLinkHover}
                    tagName='a'
                    props={{
                        onClick: this.handleOpen
                }}>
                    {this.props.label.tracking}
                </StyledComponent>
                
            </div>
        )
    }
   
}


const STYLES = {
    trackingLink: { 
        color: '#555',
        cursor: 'pointer',
        textDecoration: 'underline'
    },
    trackingLinkHover: {
        color: GoaBrand.getPrimaryColor()//'rgb(21, 22, 176)'
    }
}