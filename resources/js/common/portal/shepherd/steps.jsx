import history from "./../history";
import './shepherd-styles.css'
const Steps = [
  {
    id: "Intro",
    beforeShowPromise: function () {
      return new Promise(function (resolve) {
        history.push("/portal");
        resolve();
      });
    },
    buttons: [
      {
        classes: "shepherd-button-primary",
        text: "Next",
        type: "next",
      },
    ],
    highlightClass: "highlight",
    scrollTo: false,
    cancelIcon: {
      enabled: true,
    },
    title: "Welcome!",
    text: [
      "Take a moment to familiarize yourself with the platform.",
    ],
  },
  {
    id: 'profileSelect',
    beforeShowPromise: function () {
      return new Promise(function (resolve) {
        history.push("/portal");
        resolve();
      });
    },
    attachTo: { element: '.profile', on: 'bottom-end' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Back',
        type: 'back'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Next',
        type: 'next'
      }
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 10,
    modalOverlayOpeningPadding: 5,
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    cancelIcon: {
      enabled: true,
    },
    title: 'Profile',
    text: ['Click here to access your account information.'],
  },
  {
    id: 'Profile',
    attachTo: { element: '.BasicInfo', on: 'right' },
    beforeShowPromise: function () {
      return new Promise(function (resolve) {
        history.push("/portal/profile");
        resolve();
      });
    },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Back',
        type: 'back'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Next',
        type: 'next'
      }
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 10,
    modalOverlayOpeningPadding: 5,
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    cancelIcon: {
      enabled: true,
    },
    title: 'Profile',
    text: ['Make sure your information is correct.'],
  },
  {
    id: 'Profile',
    attachTo: { element: '.ACH', on: 'right' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Back',
        type: 'back'
      },
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 10,
    modalOverlayOpeningPadding: 5,
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    cancelIcon: {
      enabled: true,
    },
    title: 'Profile',
    text: ['Click here to add a payment method'],
  },
  {
    id: 'achForm',
    attachTo: { element: '.ACH-form', on: 'right' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Next',
        type: 'next'
      }
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 20,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Adresses',
    text: ['Input your account details to save your payment information.'],
  },
  {
    id: 'addressSelect',
    beforeShowPromise: function () {
      return new Promise(function (resolve) {
        history.push("/portal");
        resolve();
      });
    },
    attachTo: { element: '.addresses', on: 'right' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Next',
        type: 'next'
      }
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 10,
    modalOverlayOpeningPadding: 5,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Adresses',
    text: ['Click here to access the address tab.'],
  },
  {
    id: 'addresses',
    attachTo: { element: '.addressCreate', on: 'bottom' },
    beforeShowPromise: function () {
      return new Promise(function (resolve) {
        history.push("/portal/addresses");
          resolve();
      });
    },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Back',
        type: 'back'
      },
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 10,
    modalOverlayOpeningPadding: 5,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Adresses',
    text: ['Click here to add a saved address.'],
  },
  {
    id: 'addresses',
    attachTo: { element: '.addAddress', on: 'right' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Next',
        type: 'next'
      }
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 20,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Adresses',
    text: ['Input the address or addresses where you will be shipping your items from and where you would like the return items to go. These will auto populate for you in the future to make the shipping process a breeze!'],
  },
  {
    id: 'addressSelect',
    beforeShowPromise: function () {
      return new Promise(function (resolve) {
        history.push("/portal");
        resolve();
      });
    },
    attachTo: { element: '.packages', on: 'right' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Next',
        type: 'next'
      }
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 10,
    modalOverlayOpeningPadding: 5,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Packages',
    text: ['Click here to access the packages tab.'],
  },
  {
    id: 'packageSelect',
    attachTo: { element: '.packageCreate', on: 'bottom' },
    beforeShowPromise: function () {
      return new Promise(function (resolve) {
        history.push("/portal/packages");
          resolve();
      });
    },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Back',
        type: 'back'
      },
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 10,
    modalOverlayOpeningPadding: 5,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Packages',
    text: ['Create your custom packages here.'],
  },
  {
    id: 'packageAdd',
    attachTo: { element: '.addPackage', on: 'right' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Next',
        type: 'next'
      }
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 20,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Packages',
    text: ['You can input the dimensions of your different package types here and save them so you don’t ever have to do it again!'],
  },
  {
    id: 'walletSelect',
    beforeShowPromise: function () {
      return new Promise(function (resolve) {
        history.push("/portal/profile");
        resolve();
      });
    },
    attachTo: { element: '.wallet', on: 'bottom' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 10,
    modalOverlayOpeningPadding: 5,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Wallet',
    text: ['Click here to access your wallet.'],
  },
  {
    id: 'walletAdd',
    attachTo: { element: '.walletAdd', on: 'right' },
    buttons: [
      {
        classes: 'shepherd-button-secondary',
        text: 'Exit',
        type: 'cancel'
      },
      {
        classes: 'shepherd-button-primary',
        text: 'Finish',
        type: 'next'
      }
    ],
    highlightClass: false,
    modalOverlayOpeningRadius: 20,
    cancelIcon: {
      enabled: true,
    },
    popperOptions: {
      modifiers: [{ name: "offset", options: { offset: [0, 20] } }]
    },
    title: 'Wallet',
    text: ['Here you can add your payment type and fill your wallet.'],
    when: {
      hide: () => {
          history.push("/portal", {showAddFunds: false});
      },
    },
  },
  

  //...
]

export default Steps;