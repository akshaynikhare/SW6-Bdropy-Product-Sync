const ApiService = Shopware.Classes.ApiService;

class AdminConfigService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'slox_product_sync') {
        super(httpClient, loginService, apiEndpoint);
    }



    async fullsync() {
        const apiRoute = `${this.getApiBasePath()}/fullsync`;

        return await this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    async fullsyncStatus() {
        const apiRoute = `${this.getApiBasePath()}/fullsync_status`;

        return await this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    async newsync() {
        const apiRoute = `${this.getApiBasePath()}/newsync`;

        return await this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    async newsyncStatus() {
        const apiRoute = `${this.getApiBasePath()}/newsync_status`;

        return await this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    async olddelete() {
        const apiRoute = `${this.getApiBasePath()}/olddelete`;

        return await this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    async olddeleteStatus() {
        const apiRoute = `${this.getApiBasePath()}/olddelete_status`;

        return await this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    async productupdate() {
        const apiRoute = `${this.getApiBasePath()}/productupdate`;

        return await this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    async productupdateStatus() {
        const apiRoute = `${this.getApiBasePath()}/productupdate_status`;

        return await this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }



}

export default AdminConfigService;
