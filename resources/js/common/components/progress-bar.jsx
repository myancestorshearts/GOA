


import React from 'react';
import GoaBrand from '../brand';

const ProgressBar = (props) => {
  const { backgroundColor = GoaBrand.getPrimaryColor(), percentCompleted } = props;

  let fillerStyles = {
    ...STYLES.filler,
    width: `${percentCompleted}%`,
    backgroundColor: backgroundColor
  }

  return (
    <div style={STYLES.container}>
      <div style={fillerStyles}>
        <span style={STYLES.label}>{`${percentCompleted.toFixed(0)}%`}</span>
      </div>
    </div>
  );
};

export default ProgressBar;


const STYLES = {
  label: {
    padding: 5,
    color: 'white',
    fontWeight: 'bold',
    fontSize: '20px',
    fontFamily: 'poppins'
  },
  filler: {
    height: '100%',
    borderRadius: 'inherit',
    textAlign: 'right'
  },
  container: {
    height: '30px',
    width: '100%',
    backgroundColor: "#e0e0de",
    borderRadius: 50
  }
}