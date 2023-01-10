
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

    watch: {
        currentMappings: {
            deep: true,
            immediate: true,
            handler(to, from) {
                // if (from === undefined || to === from) {
                //     console.log('nochnage');
                //     return;
                // }
                // console.log('change');

                this.currentMappings.forEach((currentMapping, currIndex) => {
                    this.bdropyCatorgey.forEach((bdElement, bdIndex) => {
                        if (bdElement.value === currentMapping.BdropyCat.value) {
                            this.bdropyCatorgey.splice(bdIndex, 1);
                        }
                    });
                });
            }
        }
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
            const temp1obj = [];
            temp1obj = await this.AdminConfigService.getCurrentMappingsTree();
            console.log(temp1obj);
            // if (temp1obj.catogeryTree) {
            //     var temp2Obj =temp1obj.catogeryTree;
            //     if (
            //         typeof temp2Obj === 'object' &&
            //         !Array.isArray(temp2Obj) &&
            //         temp2Obj !== null
            //     ) {
            //         this.bdropyCatorgey = Object.values(temp2Obj);
            //         this.isBdropyCatloading=false;
            //     }
            // }
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
            return ;
        },

        async deleteMappingServer(BdropyCat_value) {
            const temp1obj = await this.AdminConfigService.deleteMappingServer(BdropyCat_value);
            return ;
        },



        addNewMapping() {

            this.isBdropyCatAdding=true;
            const selBdropyCat= this.bdropyCatorgey .find( c => c.value === this.newMapping.BdropyCatID);
            
            const tem1 = this.addNewMappingServer(selBdropyCat,this.newMapping.ourCatID);
            this.refreshCurrentMappings();

            // this.currentMappings.push({
            //     BdropyCat: selBdropyCat,
            //     ourCat: this.newMapping.ourCatID
            // });

            this.newMapping.BdropyCatID=null;
            this.newMapping.ourCatID=null;
            this.isBdropyCatAdding=false;
        },
        
        deleteMapping(BdropyCat_value) {

            this.isLoading=true;
            
            const tem1 = this.deleteMappingServer(BdropyCat_value);
            this.refreshCurrentMappings();

            this.isLoading=false;


            // this.currentMappings.forEach((element, index) => {
            //     if (element.BdropyCat.value === BdropyCat_value) {
            //         this.currentMappings.splice(index, 1);
            //     }
            // });
        }
    },


});
