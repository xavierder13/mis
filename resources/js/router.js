import Vue from 'vue';
import Router from 'vue-router';
import Home from './views/Home.vue';
import Login from './auth/Login.vue';
import Dashboard from './views/dashboard/Dashboard.vue';
import UserIndex from './views/user/UserIndex.vue';
import DepartmentIndex from './views/department/DepartmentIndex.vue';
import ManagerIndex from './views/manager/ManagerIndex.vue';
import RefNoSetting from './views/ref_no_setting/RefNoSetting.vue';
import PageNotFound from './404/PageNotFound.vue';

Vue.use(Router);

const routes = [
  {
    path: '/',
    name: 'home',
    component: Home,
    children: [
      {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
      },
      {
        path: '/user/index',
        name: 'user.index',
        component: UserIndex,
      },
      {
        path: '/department/index',
        name: 'department.index',
        component: DepartmentIndex,
      },
      {
        path: '/manager/index',
        name: 'manager.index',
        component: ManagerIndex,
      },
      {
        path: '/ref_no_setting/index',
        name: 'ref_no_setting.index',
        component: RefNoSetting,
      },
    ],
    beforeEnter(to, from, next) {

      if (localStorage.getItem('access_token')) {
        next();
      }
      else {
        next('/login');
      }
    }
  },
  {
    path: '/login',
    name: 'login',
    component: Login,
    beforeEnter(to, from, next) {

      if (localStorage.getItem('access_token')) {
        next('/');
      }
      else {
        next();
      }
    }
  },
  {
    path: '*',
    component: PageNotFound,
  },
];

const router = new Router({
  routes: routes
});

export default router;