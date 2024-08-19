
import WebClient from '../webclient';

const HOST = 'https://app.goasolutions.com';

class Tokens {

    /**purpose
     *   generate tokens
     * args
     *   (none)
     * returns
     *   tokens
     */
    static generate(parameters, successCallback, failureCallback) {
        WebClient.basicPost(parameters, HOST + '/restapi/tokens/generate', successCallback, failureCallback, {
            Authorization: btoa("83f9df15b4025701c49ac6a728ee63b:7dc2feb5bcd16a794548e3fa0126089")
        }); // live
        //WebClient.basicPost(parameters, ' http://goaship.com/restapi/tokens/generate', successCallback, failureCallback, { Authorization: btoa("079efb0958d6a2243bcf17ae683d1c4:60461eb4922aacc353f7d0e197df5b8") }); // zack local
        //WebClient.basicPost(parameters, ' http://goaship.com/restapi/tokens/generate', successCallback, failureCallback, { Authorization: btoa("ae2a0c96385791361c48f0e25fbb4d7:bd71872f3c85090b1a3e5ec24d466a9") }); // kyle locAl
    }
}



class Shipment {

    /**purpose
     *   rate shipment only that does not save 
     * args
     *   from_address_id (optional)
     *   from_postal (optional) (required if no from_address_id) 
     *   to_postal (required)
     *   package (required)
     *   services (optional) (SIGNATURE, ADULT_SIGNATURE)
     * returns
     *   shipment
     */
    static rateOnly(parameters, successCallback, failureCallback, token) {
        WebClient.basicPost(parameters, HOST + '/restapi/shipment/rate/only', successCallback, failureCallback, { Authorization: ('Bearer ' + token) });
    }

}



export default class RestApi {
    static Tokens = Tokens;
    static Shipment = Shipment;
}