
import WebClient from '../webclient';

class Referral {

    /**purpose
     *   export all labels printed
     * args
     *   start (optional)
     *   end (optional)
     *   user_id (required)
     * returns 
     *   export.csv
     */
    static labelsExport(parameters) {
        WebClient.basicDownload(parameters, '/apireferral/labels/export');
    }

    /**purpose
     *   export latest urls labels of each service
     * args
     *   user_id (required)
     * returns 
     *   export.csv
     */
    static labelsLatestExport(parameters) {
        WebClient.basicDownload(parameters, '/apireferral/labels/latest/export');
    }

    /**purpose
     *   export total charges for each service
     * args
     *   start (optional)
     *   end (optional)
     *   user_id (required)
     * returns 
     *   export.csv
     */
    static totalsExport(parameters) {
        WebClient.basicDownload(parameters, '/apireferral/totals/export');
    }
}

export default class Admin {
    static Referral = Referral;
}