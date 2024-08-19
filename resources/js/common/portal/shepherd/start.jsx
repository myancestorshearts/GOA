import React from "react";

class Start extends React.Component {
  componentDidMount() {
    if(this.props.firstTime == 1){
      GoaApi.User.firstTimeLogin({
        first_time_login: 0
      }, success =>{
        if (this.props.startTour) {
          this.props.startTour.start();
        }
      })
    }
  }

  render() {
    return null;
  }
}


export default Start;
