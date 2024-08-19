
import React from 'react';

const FlexExpander = (props) => {

    let styles = {flex: props.flex ? props.flex : 1}
    return <div style={styles}></div>
}

export default FlexExpander;