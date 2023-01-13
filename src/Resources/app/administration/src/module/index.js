
import './slox-bdropy-import';
import './sw-product';

const { Module } = Shopware;

Module.register('slox-bdropy', {
    type: 'plugin',
    name: 'slox-bdropy',
    description: 'slox-bdropy',
    color: '#9AA8B5',
    icon: 'default-device-server',
    favicon: 'icon-module-settings.png',

    routes: {
        productimport: {
            component: 'slox-bdropy-import',
            path: 'productimport',
            privilege: 'system.slox-product-import',
            redirect: {
                name: 'slox.bdropy.productimport.fullsync'
            },
            children: {
                fullsync: {
                    component: 'slox-bdropy-import-fullsync',
                    path: 'fullsync'
                },
                newsync: {
                    component: 'slox-bdropy-import-newsync',
                    path: 'newsync'
                },
                olddelete: {
                    component: 'slox-bdropy-import-olddelete',
                    path: 'olddelete'
                },
                productupdate: {
                    component: 'slox-bdropy-import-productupdate',
                    path: 'productupdate'
                },
            }
        }
    },

    navigation: [
        {
            id: 'slox-Bdropy-nav',
            path: 'slox.bdropy.productimport',
            label: 'Bdropy Sync',
            position: 10000,
            parent: 'sw-extension'
        }
    ],

    extensionEntryRoute: {
        extensionName: 'slox-product-import',
        route: 'slox.bdropy.productimport'
    }



});
