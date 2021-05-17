import Vue from 'vue';
import Router from 'vue-router';
import Home from './views/Home.vue';
import Login from './auth/Login.vue';
import Dashboard from './views/dashboard/Dashboard.vue';
import ProgrammerReports from './views/programmer_reports/ProgrammerReports.vue';
import ProjectLogs from './views/project_logs/ProjectLogs.vue';
import UserIndex from './views/user/UserIndex.vue';
import DepartmentIndex from './views/department/DepartmentIndex.vue';
import ManagerIndex from './views/manager/ManagerIndex.vue';
import RefNoSetting from './views/ref_no_setting/RefNoSetting.vue';
import Holiday from './views/holiday/Holiday.vue';
import Permission from './views/permission/PermissionIndex.vue';
import Role from './views/role/RoleIndex.vue';
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
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('project-list') || user_permissions.includes('project-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/programmer_reports',
        name: 'programmer.reports',
        component: ProgrammerReports,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('programmer-projects'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/project_logs/:project_id',
        name: 'project.logs',
        component: ProjectLogs,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('project-log-list') || user_permissions.includes('project-log-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/project_logs/:project_id/:endorse_project_id',
        name: 'endorse_project.logs',
        component: ProjectLogs,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('project-log-list') || user_permissions.includes('project-log-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/user/index',
        name: 'user.index',
        component: UserIndex,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('user-list') || user_permissions.includes('user-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/department/index',
        name: 'department.index',
        component: DepartmentIndex,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('department-list') || user_permissions.includes('department-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/manager/index',
        name: 'manager.index',
        component: ManagerIndex,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('manager-list') || user_permissions.includes('manager-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/ref_no_setting/index',
        name: 'ref_no_setting.index',
        component: RefNoSetting,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('ref-no-setting'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/holiday/index',
        name: 'holiday.index',
        component: Holiday,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('holiday-list') || user_permissions.includes('holiday-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/permission/index',
        name: 'permission.index',
        component: Permission,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('permission-list') || user_permissions.includes('permission-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
      },
      {
        path: '/role/index',
        name: 'role.index',
        component: Role,
        beforeEnter(to, from, next)
        { 
          let user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
          if(user_permissions.includes('role-list') || user_permissions.includes('role-create'))
          {
            next();
          }
          else
          {
            next('/unauthorize');
          }
        }
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