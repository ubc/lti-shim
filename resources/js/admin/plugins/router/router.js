import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)

import Splash from '../../views/SplashView'
import Login from '../../views/LoginView'
import Admin from '../../views/AdminView'

import adminRoutes from './adminRoutes'

export default new VueRouter({
  routes: [
    {
      path: '/',
      name: 'splash',
      component: Splash
    },
    {
      path: '/login',
      name: 'login',
      component: Login
    },
    {
      path: '/admin',
      component: Admin,
      children: adminRoutes
    }
  ]
})
