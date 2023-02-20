import template from './template.html.twig';
import './style.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('slox-bdropy-import-olddelete', {
    template,

    mixins: [
        Mixin.getByName('notification'),
    ],
    inject: [
        'AdminControlService'
    ],

    props: {
        output: {
            type: String,
            default() {
                return '';
            }
        },
        lastSynced: {
            type: String,
            default() {
                return '00:00:00 T00:00';
            }
        },
        defaultItem: {
            type: String,
            default() {
                return '';
            }
        },
        isRunning: {
            type: String,
            default() {
                return 'FALSE';
            }
        },
        islastUpdateCounter: {
            type: Number,
            default() {
                return 6;
            }
        },
    },

    data() {
        return {
            isLoading: true,
            isDisabled: true,
            _timeout: null
        };
    },
    watch: {
        'isRunning'() {
            this.onSubmitStatus();
        }
    },
    methods: {
        async onSubmitStatus() {
            let response = await this.AdminControlService.olddeleteStatus();
            if (response.log) {
                this.output = response.log.trim();
                this.lastSynced = response.lastRun.trim();
            }
            this._timeout = setTimeout(this.onSubmitStatus, 2000);
            this.isRunning = 'TRUE';
            this.isLoading = true;
            if (response.isRunning=='FALSE') {
                if (this.islastUpdateCounter > 10) {
                    clearInterval(this._timeout);
       
                    this.isLoading = false;
                } else {
                    this.islastUpdateCounter = this.islastUpdateCounter + 1;
                }
            }
            console.log("checking status=" + this.islastUpdateCounter);

        },
        async onSubmit() {
            clearInterval(this._timeout);
            if (!this.isRunning) {
                this.islastUpdateCounter = 0;
                this._timeout = setTimeout(this.onSubmitStatus, 2000);
                this.isLoading = true;
                this.isRunning =  'TRUE';
            }
            let response = await this.AdminControlService.olddelete();
        },
        async onDeleteAll() {
            clearInterval(this._timeout);
            if (!this.isRunning) {
                this.islastUpdateCounter = 0;
                this._timeout = setTimeout(this.onSubmitStatus, 2000);
                this.isLoading = true;
                this.isRunning = true;
            }
            let response = await this.AdminControlService.olddeleteAll();
        },

        
    },

    async mounted() {
        this._timeout = setTimeout(this.onSubmitStatus, 2000);
    },
});

