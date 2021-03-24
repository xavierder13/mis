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
        <v-list-item link :to="{ name: 'dashboard' }">
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>
        <v-list-item link :to="{ name: 'programmer.reports' }">
          <v-list-item-icon>
            <v-icon>mdi-file-document</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Reports</v-list-item-title>
        </v-list-item>
        <v-list-item
          link
          :to="{ name: 'user.index' }"
          v-if="user_type == 'Admin'"
        >
          <v-list-item-icon>
            <v-icon>mdi-account-arrow-right-outline</v-icon>
          </v-list-item-icon>
          <v-list-item-title>User</v-list-item-title>
        </v-list-item>

        <v-list-group no-action v-if="user_type == 'Admin'">
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-cog</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>Settings</v-list-item-title>
            </v-list-item-content>
          </template>
          <!-- List Group Items -->
          <v-list-item link to="/department/index">
            <v-list-item-content>
              <v-list-item-title>Department</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/manager/index">
            <v-list-item-content>
              <v-list-item-title>Manager</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/ref_no_setting/index">
            <v-list-item-content>
              <v-list-item-title>Ref No. Settings</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/holiday/index">
            <v-list-item-content>
              <v-list-item-title>Holiday</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/role/index">
            <v-list-item-content>
              <v-list-item-title>Role</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/permission/index">
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
        project_create: false,
        project_edit: false,
        project_delete: false,
        project_log_create: false,
        project_log_edit: false,
        project_log_delete: false,
        user_create: false,
        user_edit: false,
        user_delete: false,
        department_create: false,
        department_edit: false,
        department_delete: false,
        manager_create: false,
        manager_edit: false,
        manager_delete: false,
        holiday_create: false,
        holiday_edit: false,
        holiday_delete: false,
        role_create: false,
        role_edit: false,
        role_delete: false,
        permission_create: false,
        permission_edit: false,
        permission_delete: false,
        ref_no_setting: false,
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
        console.log(response.data);
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
      this.permissions.project_create = this.hasPermission(["project-create"]);
      this.roles.administrator = this.hasRole(["Administrator"]);
      console.log(this.permissions.project_create);
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
          // console.log(user_permissions.includes(value));
        }
      });

      return hasPermission;
    },
    pusher() {
      Pusher.logToConsole = true;

      let pusher = new Pusher("774f9a22d535560d6c08", {
        cluster: "ap1",
        encrypted: true,
      });

      //Subscribe to the channel we specified in our Adonis Application
      let channel = pusher.subscribe("happypatient-event");

      channel.bind("App\\Events\\EventNotification", (data) => {
        //PUSHER - refresh fetch/refresh roles and permissions
        if (
          data.action == "role-create" ||
          data.action == "role-edit" ||
          data.action == "role-delete" ||
          data.action == "user-delete"
        ) {
          this.userRolesPermissions();
        }
      });
    },
  },

  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getUser();
    this.getRolesPermissions();
  },
};
</script>