import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

import template from './template.html.twig';
import './style.scss';

import './fullsync';
import './newsync';
import './olddelete';
import './productupdate';

const { Component, Mixin } = Shopware;
const { Criteria }         = Shopware.Data;
const { mapGetters }       = Shopware.Component.getComponentHelper();

Component.register('slox-bdropy-import', {
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },
    template,
    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            isLoading: true
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier)
        };
    },

    computed: {
        defaultCriteria() {
            return new Criteria();
        }
    },

    created() {
        this.createdComponent();
    },

    watch: {
 
    },

    props: {
    },

    methods: {
        async createdComponent() {
            this.isLoading = false;
        }
    }

});

