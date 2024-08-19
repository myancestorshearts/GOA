
import React from 'react';

export default class Print85X11D4 extends React.Component {

    constructor(props) {
        super(props);
    }

    render() {

        let dpi = this.props.width / 8.5;

        let containerStyles = {
            width: (dpi * 8.5) + 'px'
        }

        let imageStyles = {
            width: (dpi * 4) + 'px',
            height: (dpi * 5) + 'px',
            marginLeft: (dpi * .15) + 'px',
            marginRight: (dpi * .1) + 'px',
            marginTop: (dpi * .4) + 'px',
            marginBottom: (dpi * .1) + 'px'
        }

        let images = this.props.images.map((x, i) => <img key={i} src={x} style={imageStyles} />)

        return (
            <div style={containerStyles}>
                {images}
            </div>
        )
    }
}