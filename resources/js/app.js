import Vue from 'vue'
import Example from './components/CategoryComponent.vue'
import {BootstrapVue, BootstrapVueIcons} from 'bootstrap-vue'

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.use(BootstrapVue)
Vue.use(BootstrapVueIcons)

let vue = new Vue({
    render: h => h(Example, {

    }),
})

vue.$mount('#app')

