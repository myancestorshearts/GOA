
import React from 'react';

import Text from './text';

import FlexContainer from '../components/flex-container';

import Functions from '../functions';

export default class Weight extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            lbs: '',
            ozs: ''
        }

        if (props.value) {

            let totalOz = Math.round(props.value);

            this.state = {
                lbs: this.calcLbs(totalOz),
                ozs: this.calcOzs(totalOz),
            }
        }

        this.handleBlur = this.handleBlur.bind(this);
    }

    handleUpdateValue(value) {
        let totalOz = Math.round(value);
        this.setState({
            lbs: this.calcLbs(totalOz),
            ozs: this.calcOzs(totalOz),
        })
    }

    calcOzs(totalOz) {
        let ozs = Math.round(totalOz % 16);
        return ozs == 0 ? '' : ozs;
    }

    calcLbs(totalOz) {
        let lbs = Math.floor(totalOz / 16);
        return lbs == 0 ? '' : lbs;
    }

    handleBlur() {
        let totalOz = 0;
        if (this.inputLbs) totalOz += Functions.isFloat(this.inputLbs.value) ? Number(this.inputLbs.value * 16) : 0;
        if (this.inputOzs) totalOz += Functions.isFloat(this.inputOzs.value) ? Number(this.inputOzs.value) : 0;

        this.setState({
            lbs: this.calcLbs(totalOz),
            ozs: this.calcOzs(totalOz),
        })

        if (this.props.onChange) this.props.onChange(totalOz)
    }

    render() {

        // merge flex into container styles
        let containerStyles = { ...STYLES.container }
        if (this.props.stylescontainer) containerStyles = { ...containerStyles, ...this.props.stylescontainer };


        let gap = this.props.gap ? this.props.gap : '10px';

        return (
            <FlexContainer gap={gap}>
                <Text
                    ref={e => this.inputLbs = e}
                    color={this.props.color}
                    stylesinput={this.props.stylesinput}
                    styleslabel={this.props.styleslabel}
                    stylescontainer={{ ...containerStyles, ...STYLES.firstContainer }}
                    title='lb(s)'
                    value={this.state.lbs}
                    onChange={(e) => this.setState({ lbs: e.target.value })}
                    onBlur={this.handleBlur}
                />
                <Text
                    ref={e => this.inputOzs = e}
                    color={this.props.color}
                    stylesinput={this.props.stylesinput}
                    styleslabel={this.props.styleslabel}
                    stylescontainer={containerStyles}
                    title='oz(s)'
                    value={this.state.ozs}
                    onChange={(e) => this.setState({ ozs: e.target.value })}
                    onBlur={this.handleBlur}
                />
            </FlexContainer>
        )
    }
}


const STYLES = {
    container: {
        flex: 1,
        minWidth: '50px'
    },
    firstContainer: {
        marginBottom: '0px'
    }
}
