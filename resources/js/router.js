import Vue from 'vue';
import Router from 'vue-router';
import Home from './views/Home.vue';
import Login from './auth/Login.vue';
import Dashboard from './views/dashboard/Dashboard.vue';
import ProgrammerReports from './views/programmer_reports/ProgrammerReports.vue';
import ProjectLogs from './views/project_logs/ProjectLogs.vue';
import UserIndex from './views/user/UserIndex.vue';
import UserCreate from './views/user/UserCreate.vue';
import DepartmentIndex from './views/department/DepartmentIndex.vue';
import ManagerIndex from './views/manager/ManagerIndex.vue';
import RefNoSetting from './views/ref_no_setting/RefNoSetting.vue';
import Holiday from './views/holiday/Holiday.vue';
import Permission from './views/permission/PermissionIndex.vue';
import Role from './views/role/RoleIndex.vue';
import ProjectAcceptance from './views/project_acceptance/ProjectAcceptance.vue';
import ActivityLogs from './views/activity_logs/ActivityLogs.vue';
import PageNotFound from './404/PageNotFound.vue';
import Unauthorize from './401/Unauthorize.vue';

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
        path: '/programmer_reports',
        name: 'programmer.reports',
        component: ProgrammerReports,
      
      },
      {
        path: '/project_logs/:project_id',
        name: 'project.logs',
        component: ProjectLogs,
        
      },
      {
        path: '/project_logs/:project_id/:endorse_project_id',
        name: 'endorse_project.logs',
        component: ProjectLogs,
        
      },
      {
        path: '/user/index',
        name: 'user.index',
        component: UserIndex,
        
      },
      {
        path: '/user/create',
        name: 'user.create',
        component: UserCreate,
        
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
      {
        path: '/holiday/index',
        name: 'holiday.index',
        component: Holiday,
        
      },
      {
        path: '/permission/index',
        name: 'permission.index',
        component: Permission,
        
      },
      {
        path: '/role/index',
        name: 'role.index',
        component: Role,
        
      },
      {
        path: '/project_acceptance/:project_id',
        name: 'project_acceptance',
        component: ProjectAcceptance,
        
      },
      {
        path: '/activity_logs',
        name: 'activity_logs',
        component: ActivityLogs,
        
      },
      {
        path: '/unauthorize',
        name: 'unauthorize',
        component: Unauthorize,
      }
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