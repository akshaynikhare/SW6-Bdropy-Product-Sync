const { Application } = Shopware;

import AdminControlService from './AdminControl.service';
import AdminConfigService from './AdminConfig.service';


Application.addServiceProvider('AdminControlService', (container) => {
    const initContainer = Application.getContainer('init');
    return new AdminControlService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('AdminConfigService', (container) => {
    const initContainer = Application.getContainer('init');
    return new AdminConfigService(initContainer.httpClient, container.loginService);
});

