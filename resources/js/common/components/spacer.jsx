

import React from 'react';
const Spacer = ({space = '10px'}) => {

    let styles = {
        minWidth: space,
        minHeight: space
    }

    return (
        <div style={styles}></div>
    )
}

export default Spacer;
