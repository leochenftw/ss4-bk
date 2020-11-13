// Polyfills
import "core-js/stable"
import "regenerator-runtime/runtime"

import Vue from "vue"
import vuetify from "./plugins/vuetify" // path to vuetify export
import App from "./vue/App.vue"
import router from "./vue/router"

new Vue({
  el: "#app",
  vuetify,
  router,
  components: {
    App
  },
  template: "<App/>"
})
