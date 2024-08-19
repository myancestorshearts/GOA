import React from 'react';
import CommonFunctions from '../functions';
import Select from './select';
const StateSelect = (props) => {

    let options = Object.keys(CommonFunctions.ADDRESS_STATES).map((x, i) => {
        return ({
            label: (Object.values(CommonFunctions.ADDRESS_STATES))[i],
            value: x
        })
    })
    options.unshift({ label: 'Select State', value: null })
    return (
        <Select
            title='State'
            stylescontainer={STYLES.input}
            styleslabel={{ ...STYLES.label, ...(props.styleslabel ? props.styleslabel : {}) }}
            stylesselect={{ ...STYLES.select, ...(props.stylesselect ? props.stylesselect : {}) }}
            options={options}
            value={props.value}
            onChange={e => props.handleChange(e)}
        />
    )
}

const STYLES = {
    input: {
        flex: 1,
        minHeight: '36px'
    },
    label: {
        marginBottom: '2px',
        fontSize: '16px',
        fontWeight: 'bold'
    },
    select: {
        minHeight: '36px',
        marginTop: 0
    }
}

export default StateSelect;