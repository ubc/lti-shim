import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)

import Splash from '../../views/Splash'

export default new VueRouter({
  routes: [
    {
      path: '/',
      name: 'splash',
      component: Splash 
    }
  ]
})
