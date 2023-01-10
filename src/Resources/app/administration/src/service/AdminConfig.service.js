const ApiService = Shopware.Classes.ApiService;

class AdminConfigService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'slox_product_sync') {
        super(httpClient, loginService, apiEndpoint);
    }

    async credEnquire(user, password) {
        const apiRoute = `${this.getApiBasePath()}/credenquire`;
        return await this.httpClient.post(
            apiRoute,
            {
                user: user,
                password: password,
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    async getBdropyCatogeryTree() {

        const apiRoute = `${this.getApiBasePath()}/bdropy/catogerytree`;

        return await this.httpClient.post(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {

            return ApiService.handleResponse(response);

        });
    }


    async getCurrentMappingsTree() {

        const apiRoute = `${this.getApiBasePath()}/bdropy/currentmappings`;

        return await this.httpClient.post(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {

            return ApiService.handleResponse(response);

        });
    }

    async addNewMappingServer(selBdropyCat, ourCatID) {

        const apiRoute = `${this.getApiBasePath()}/bdropy/addmappings`;

        return await this.httpClient.post(
            apiRoute,
            {
                sel_bdropy_cat: selBdropyCat, 
                our_cat_id: ourCatID,
                headers: this.getBasicHeaders()
            }
        ).then((response) => {

            return ApiService.handleResponse(response);

        });
    }
    async deleteMappingServer(BdropyCat_value) {

        const apiRoute = `${this.getApiBasePath()}/bdropy/deletemappings`;

        return await this.httpClient.post(
            apiRoute,
            {
                bdropy_cat_value: BdropyCat_value ,
                headers: this.getBasicHeaders()
            }
        ).then((response) => {

            return ApiService.handleResponse(response);

        });
    }

    async deleteallmappings() {

        const apiRoute = `${this.getApiBasePath()}/bdropy/deleteallmappings`;

        return await this.httpClient.post(
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
