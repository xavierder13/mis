<template>
  <v-app>
    <!-- Navbar -->
    <v-app-bar dense dark app>
      <v-btn icon @click.stop="mini = !mini">
        <v-app-bar-nav-icon></v-app-bar-nav-icon>
      </v-btn>

      <v-spacer></v-spacer>

      <v-btn @click="logout">
        <v-icon>mdi-logout </v-icon>
        Logout
      </v-btn>
    </v-app-bar>

    <!-- Sidebar -->
    <v-navigation-drawer v-model="drawer" :mini-variant.sync="mini" dark app>
      <v-list>
        <v-list-item class="px-2">
          <v-list-item-avatar class="rounded-5" height="60" width="60">
            <v-img src="/img/mis-icon.svg"></v-img>
          </v-list-item-avatar>
          <v-list-item-content>
            <v-list-item-title>Information System</v-list-item-title>
            <v-list-item-subtitle>{{ user }}</v-list-item-subtitle>
          </v-list-item-content>
        </v-list-item>
      </v-list>

      <v-divider></v-divider>

      <v-list>
        <v-list-item
          link
          :to="{ name: 'dashboard' }"
          v-if="permissions.project_list || permissions.project_create"
        >
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>
        <v-list-item
          link
          :to="{ name: 'programmer.reports' }"
          v-if="permissions.programmer_projects"
        >
          <v-list-item-icon>
            <v-icon>mdi-file-document</v-icon>
          </v-list-item-icon>
          <v-list-item-title>My Projects</v-list-item-title>
        </v-list-item>
        <v-list-item
          link
          :to="{ name: 'user.index' }"
          v-if="permissions.user_list || permissions.user_create"
        >
          <v-list-item-icon>
            <v-icon>mdi-account-arrow-right-outline</v-icon>
          </v-list-item-icon>
          <v-list-item-title>User</v-list-item-title>
        </v-list-item>

        <v-list-group
          no-action
          v-if="
            permissions.department_list ||
            permissions.department_create ||
            permissions.manager_list ||
            permissions.manager_create ||
            permissions.holiday_list ||
            permissions.holiday_create ||
            permissions.role_list ||
            permissions.role_create ||
            permissions.permission_list ||
            permissions.permission_create ||
            permissions.ref_no_setting
          "
        >
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-cog</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>Settings</v-list-item-title>
            </v-list-item-content>
          </template>
          <!-- List Group Items -->
          <v-list-item
            link
            to="/department/index"
            v-if="permissions.department_list || permissions.department_create"
          >
            <v-list-item-content>
              <v-list-item-title>Department</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/manager/index"
            v-if="permissions.manager_list || permissions.manager_create"
          >
            <v-list-item-content>
              <v-list-item-title>Manager</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/ref_no_setting/index"
            v-if="permissions.ref_no_setting"
          >
            <v-list-item-content>
              <v-list-item-title>Ref No. Settings</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/holiday/index"
            v-if="permissions.holiday_list || permissions.holiday_create"
          >
            <v-list-item-content>
              <v-list-item-title>Holiday</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/role/index"
            v-if="permissions.role_list || permissions.role_create"
          >
            <v-list-item-content>
              <v-list-item-title>Role</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/permission/index"
            v-if="permissions.permission_list || permissions.permission_create"
          >
            <v-list-item-content>
              <v-list-item-title>Permission</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
      </v-list>
    </v-navigation-drawer>
    <v-overlay :absolute="absolute" :value="overlay">
      <v-progress-circular
        :size="70"
        :width="7"
        color="primary"
        indeterminate
      ></v-progress-circular>
    </v-overlay>
    <!-- Content -->
    <router-view />
  </v-app>
</template>

<script>
let access_token;
let user_permissions;
let user_roles;
import Axios from "axios";

export default {
  data() {
    return {
      absolute: true,
      overlay: false,
      drawer: true,
      mini: false,
      right: null,
      selectedItem: 1,
      user: null,
      loading: null,
      initiated: false,
      user: localStorage.getItem("user"),
      user_type: localStorage.getItem("user_type"),
      user_id: localStorage.getItem("user_id"),
      permissions: {
        project_list: false,
        project_create: false,
        project_edit: false,
        project_delete: false,
        programmer_projects: false,
        project_log_list: false,
        project_log_create: false,
        project_log_edit: false,
        project_log_delete: false,
        user_list: false,
        user_create: false,
        user_edit: false,
        user_delete: false,
        department_list: false,
        department_create: false,
        department_edit: false,
        department_delete: false,
        manager_list: false,
        manager_create: false,
        manager_edit: false,
        manager_delete: false,
        holiday_list: false,
        holiday_create: false,
        holiday_edit: false,
        holiday_delete: false,
        role_list: false,
        role_create: false,
        role_edit: false,
        role_delete: false,
        permission_list: false,
        permission_create: false,
        permission_edit: false,
        permission_delete: false,
        ref_no_setting: false,
        print_preview: false,
        import_project: false,
        export_project: false,
      },
      roles: {
        administrator: false,
      },
    };
  },

  methods: {
    getUser() {
      Axios.get("/api/user/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          // console.log(response.data);
        },
        (error) => {
          // if unauthenticated (401)
          if (error.response.status == "401") {
            localStorage.removeItem("access_token");
            this.$router.push({ name: "login" });
          }
        }
      );
    },
    logout() {
      this.overlay = true;
      Axios.get("/api/auth/logout", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          if (response.data.success) {
            this.overlay = false;
            localStorage.removeItem("access_token");
            localStorage.removeItem("user");
            this.$router.push("/login").catch(() => {});
          }
        },
        (error) => {
          this.overlay = false;
          console.log(error);

          // if unauthenticated (401)
          if (error.response.status == "401") {
            localStorage.removeItem("access_token");
            this.$router.push({ name: "login" });
          }
        }
      );
    },
    userRolesPermissions() {
      Axios.get("api/user/roles_permissions", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then((response) => {
        // console.log(response.data);
        localStorage.removeItem("user_permissions");
        localStorage.removeItem("user_roles");
        localStorage.setItem(
          "user_permissions",
          JSON.stringify(response.data.user_permissions)
        );
        localStorage.setItem(
          "user_roles",
          JSON.stringify(response.data.user_roles)
        );
        this.getRolesPermissions();
      });
    },

    getRolesPermissions() {
      this.permissions.project_list = this.hasPermission(["project-list"]);
      this.permissions.project_create = this.hasPermission(["project-create"]);
      this.permissions.project_edit = this.hasPermission(["project-edit"]);
      this.permissions.project_delete = this.hasPermission(["project-delete"]);
      this.permissions.programmer_projects = this.hasPermission([
        "programmer-projects",
      ]);
      this.permissions.project_log_list = this.hasPermission([
        "project-log-list",
      ]);
      this.permissions.project_log_create = this.hasPermission([
        "project-log-create",
      ]);
      this.permissions.project_log_edit = this.hasPermission([
        "project-log-edit",
      ]);
      this.permissions.project_log_delete = this.hasPermission([
        "project-log-delete",
      ]);
      this.permissions.user_list = this.hasPermission(["user-list"]);
      this.permissions.user_create = this.hasPermission(["user-create"]);
      this.permissions.user_edit = this.hasPermission(["user-edit"]);
      this.permissions.user_delete = this.hasPermission(["user-delete"]);
      this.permissions.department_list = this.hasPermission([
        "department-list",
      ]);
      this.permissions.department_create = this.hasPermission([
        "department-create",
      ]);
      this.permissions.department_edit = this.hasPermission([
        "department-edit",
      ]);
      this.permissions.department_delete = this.hasPermission([
        "department-delete",
      ]);
      this.permissions.manager_list = this.hasPermission(["manager-list"]);
      this.permissions.manager_create = this.hasPermission(["manager-create"]);
      this.permissions.manager_edit = this.hasPermission(["manager-edit"]);
      this.permissions.manager_delete = this.hasPermission(["manager-delete"]);
      this.permissions.holiday_list = this.hasPermission(["holiday-list"]);
      this.permissions.holiday_create = this.hasPermission(["holiday-create"]);
      this.permissions.holiday_edit = this.hasPermission(["holiday-edit"]);
      this.permissions.holiday_delete = this.hasPermission(["holiday-delete"]);
      this.permissions.permission_list = this.hasPermission([
        "permission-list",
      ]);
      this.permissions.permission_create = this.hasPermission([
        "permission-create",
      ]);
      this.permissions.permission_edit = this.hasPermission([
        "permission-edit",
      ]);
      this.permissions.permission_delete = this.hasPermission([
        "permission-delete",
      ]);
      this.permissions.role_list = this.hasPermission(["role-list"]);
      this.permissions.role_create = this.hasPermission(["role-create"]);
      this.permissions.role_edit = this.hasPermission(["role-edit"]);
      this.permissions.role_delete = this.hasPermission(["role-delete"]);
      this.permissions.ref_no_setting = this.hasPermission(["ref-no-setting"]);
      this.permissions.print_preview = this.hasPermission(["print-preview"]);
      this.permissions.import_project = this.hasPermission(["import-project"]);
      this.permissions.export_project = this.hasPermission(["export-project"]);
      this.roles.administrator = this.hasRole(["Administrator"]);
    },

    hasRole(roles) {
      user_roles = JSON.parse(localStorage.getItem("user_roles"));
      let hasRole = false;
      roles.forEach((value, index) => {
        if (user_roles.includes(value)) {
          hasRole = true;
        }
      });

      return hasRole;
    },

    hasPermission(permissions) {
      user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
      let hasPermission = false;

      permissions.forEach((value, index) => {
        if (user_permissions.includes(value)) {
          hasPermission = true;
        }
      });

      return hasPermission;
    },

    websocket() {
      window.Echo.channel("WebsocketChannel").listen("WebsocketEvent", (e) => {
        let action = e.data.action;
    
        if (
          action == "user-edit" ||
          action == "role-edit" ||
          action == "role-delete" ||
          action == "permission-delete"
        ) {
          this.userRolesPermissions();
        }
      });
    },
  },

  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getUser();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>