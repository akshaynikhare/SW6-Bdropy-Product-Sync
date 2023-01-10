
import './slox-check-cred.scss';
import template from './slox-check-cred.html.twig';

const { Component } = Shopware;


Component.register('slox-check-cred', {
    template: template,
    
    inject: ['AdminConfigService'],

    props: {
        check_output: {
            type: String,
            default() {
                return '';
            }
        },
        isLoading: {
            type: Boolean,
            default() {
                return false;
            }
        }
    },
    computed: {
        inputField_user() {
            return this.$parent.$parent.$parent.$el.querySelector('input[name="slox_product_sync.config.user"]');
        },
        inputField_password() {
            return this.$parent.$parent.$parent.$el.querySelector('input[name="slox_product_sync.config.password"]');
        }
    },
    methods: {
       
        async checkCred() {
            try {
                this.isLoading = true;
                this.check_output = '';
                let flag = 1

                if (!this.inputField_user.value) {
                    flag = 0;
                    throw 'please provide User Name';
                }
                
                if (!this.inputField_password.value) {
                    flag = 0;
                    throw 'please provide  Password';
                }
                

                if (flag) {
                    let response = await  this.AdminConfigService.credEnquire(
                                                                    this.inputField_user.value,
                                                                    this.inputField_password.value
                                                                );

                             
                        if (response.success) {
                            this.check_output = response.message;
                        } else if (response.message && response.message != '') {
                                throw response.message;
                        } else {
                            throw 'Unkown Error in Response';
                        }

                }
                this.isLoading = false;
            }
            catch (err) {
                this.check_output = 'Unexpected Error: ' + err;
                this.isLoading = false;
            }
        },
    },
    mounted() {
    },
});
