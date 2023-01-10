
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

import './style.scss';
import template from './template.html.twig';

const { Component } = Shopware;


Component.register('slox-category-maping-table', {
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    template: template,

    inject: [ 'AdminConfigService'],

    props: {
        currentMappings: {
            type: Array,
            default() {
                return []
            }
        },
        newMapping: {
            type: Array,
            default() {
                return {
                    BdropyCatID: null,
                    ourCatID: null
                }

            }
        },
        bdropyCatorgey: {
            type: Array,
            required: true,
            default() {
                return [
                    {
                        value: "Test1",
                        code: "",
                        parent_code: "",
                        label: "loading...",
                    },
                    {
                        value: "Test2",
                        code: "",
                        parent_code: "",
                        label: "loading..."
                    },
                    {
                        value: "Test3",
                        code: "",
                        parent_code: "",
                        label: "loading..."
                    }
                ]
            }
        }
    },
    data() {
        return {
            isLoading: false,
            isBdropyCatloading: true,
            isBdropyCatAdding: true,
            lastMappings: null
        };
    },
    computed: {

    },

    mounted() {
        this.isBdropyCatloading=true;
        this.mountedComponent();
    },



    methods: {
        mountedComponent() {
            this.refreshBdropyList();
            this.refreshCurrentMappings();
        },

        async refreshCurrentMappings() {
          // this.isLoading=true;
            const  temp1obj = await this.AdminConfigService.getCurrentMappingsTree();
            if (temp1obj.jsonMapping) {
                var temp2Obj =temp1obj.jsonMapping;
                if (Array.isArray(temp2Obj) && temp2Obj.length >= 0) {
                    console.log(temp1obj);
                    this.currentMappings = temp2Obj;
                    
                }
            }else{
                this.currentMappings = [];
            }


            this.currentMappings.forEach((currentMapping, currIndex) => {
                this.bdropyCatorgey.forEach((bdElement, bdIndex) => {
                    if (bdElement.value === currentMapping.BdropyCat.value) {
                        this.bdropyCatorgey.splice(bdIndex, 1);
                    }
                });
            });

            
            this.isLoading=false;
        }, 

        async refreshBdropyList() {
            var temp1obj = [];
            temp1obj = await this.AdminConfigService.getBdropyCatogeryTree();
            if (temp1obj.catogeryTree) {
                var temp2Obj =temp1obj.catogeryTree;
                if (
                    typeof temp2Obj === 'object' &&
                    !Array.isArray(temp2Obj) &&
                    temp2Obj !== null
                ) {
                    this.bdropyCatorgey = Object.values(temp2Obj);
                    this.isBdropyCatloading=false;
                }
            }
        },     
    
        async addNewMappingServer(selBdropyCat,ourCatID) {
            const temp1obj = await this.AdminConfigService.addNewMappingServer(selBdropyCat,ourCatID);
            
            this.$nextTick(() => {
                this.refreshCurrentMappings();
            });
            return ;
        },

        async deleteMappingServer(BdropyCat_value) {
            const temp1obj = await this.AdminConfigService.deleteMappingServer(BdropyCat_value);

            this.$nextTick(() => {
                this.refreshCurrentMappings();
            });
            return ;
        },

        async deleteAllMappingServer() {
            const temp1obj = await this.AdminConfigService.deleteallmappings();

            this.$nextTick(() => {
                this.refreshCurrentMappings();
            });
            return ;
        },


        addNewMapping() {

            this.isBdropyCatAdding=true;
            const selBdropyCat= this.bdropyCatorgey .find( c => c.value === this.newMapping.BdropyCatID);
            
            const tem1 = this.addNewMappingServer(selBdropyCat,this.newMapping.ourCatID);


            this.newMapping.BdropyCatID=null;
            this.newMapping.ourCatID=null;
            this.isBdropyCatAdding=false;
        },
        
        deleteMapping(BdropyCat_value) {
            this.isLoading=true;
            const tem1 = this.deleteMappingServer(BdropyCat_value);
        },
        deleteAllMapping() {
            this.isLoading=true;
            const tem1 = this.deleteAllMappingServer();
        }
    },


});
