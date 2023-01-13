import template from './template.html.twig';
import './style.scss';

Shopware.Component.register('slox-bdropy-product-link', {
    template,

    inject: [
        'repositoryFactory'
    ],

    props: {
        product: {
            required: true,
            type: Object
        }
    },

    data() {
        return {
            confirmOpen: false
        };
    },

    computed: {
        visible() {
            return this.product
                && this.product.extensions
                && this.product.extensions.sloxBDropyProduct;
        },

    },

    methods: {
        onClick() {
            if (true === this.hasChanges()) {
                this.confirmOpen = true;
                return;
            }

            this.openBDropySyncTab();
        },

        openBDropySyncTab() {
            this.$router.push({
                name: 'slox.bdropy.productimport.fullsync'
            });
        },

    }
});