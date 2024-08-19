
export default class ApiMass {

    constructor(jobCount) {
        this.jobs = [];
        this.jobsRunning = 0;
        this.jobCount = jobCount;
        this.onFinalize = () => { }
    }

    push(apiCall, args, successCallback, failureCallback) {
        this.jobs.push({
            method: apiCall,
            args: args,
            successCallback: successCallback,
            failureCallback: failureCallback
        })
    }

    finalize(method) {
        this.onFinalize = method
    }

    process() {
        while (this.jobsRunning < this.jobCount) {
            if (this.jobs.length == 0) {
                if (this.jobsRunning == 0) this.onFinalize();
                return;
            }

            let job = this.jobs.pop();

            let callbackOverride = (args, callback) => {
                this.jobsRunning--;
                this.process();
                if (callback) callback(args);
            }

            job.method(job.args, (x) => callbackOverride(x, job.successCallback), (x) => callbackOverride(x, job.failureCallback));
            this.jobsRunning++;
        }
    }

}