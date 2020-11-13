import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

export default new Router({
    mode: 'history',
    routes: [
        // {
        //     path: '/cart',
        //     name: 'Cart',
        //     component: Cart
        // },
        // {
        //     path: '/search',
        //     name: 'SearchResultEmpty',
        //     component: Result
        // },
        // {
        //     path: '/search/:term',
        //     name: 'SearchResult',
        //     component: Result
        // },
        // {
        //     path: '*',
        //     name: 'Base',
        //     component: Base
        // }
    ],
    scrollBehavior (to, from, savedPosition) {
        return { x: 0, y: 0 };
    }
})
