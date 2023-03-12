import Vue from 'vue'
import Home from './Home.vue'
import {BootstrapVue, BootstrapVueIcons} from 'bootstrap-vue'
import CategoryComponent from "../../components/CategoryComponent";
import DetailComponent from "../../components/DetailComponent";
import AnalogyDetailComponent from "../../components/AnalogyDetailComponent";

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'


/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.use(BootstrapVue)
Vue.use(BootstrapVueIcons)
Vue.component('CategoryComponent',CategoryComponent)
Vue.component('DetailComponent',DetailComponent)
Vue.component('AnalogyDetailComponent',AnalogyDetailComponent)

let vue = new Vue({
    render: h => h(Home , {

    }),
})

vue.$mount('#app')

