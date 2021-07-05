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
          v-if="userPermissions.project_list || userPermissions.project_create"
        >
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>
        <v-list-item
          link
          :to="{ name: 'programmer.reports' }"
          v-if="userPermissions.programmer_projects"
        >
          <v-list-item-icon>
            <v-icon>mdi-file-document</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Projects</v-list-item-title>
        </v-list-item>
        <v-list-group
          no-action
          v-if="
            userPermissions.user_list ||
            userPermissions.user_create
          "
        >
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-account-arrow-right-outline</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>User</v-list-item-title>
            </v-list-item-content>
          </template>
          <!-- List Group Items -->
          <v-list-item
            link
            to="/user/index"
            v-if="userPermissions.user_list"
          >
            <v-list-item-content>
              <v-list-item-title>User Record</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/user/create"
            v-if="userPermissions.user_create"
          >
            <v-list-item-content>
              <v-list-item-title>Create New</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          
        </v-list-group>

        <v-list-group
          no-action
          v-if="
            userPermissions.department_list ||
            userPermissions.department_create ||
            userPermissions.manager_list ||
            userPermissions.manager_create ||
            userPermissions.holiday_list ||
            userPermissions.holiday_create ||
            userPermissions.role_list ||
            userPermissions.role_create ||
            userPermissions.permission_list ||
            userPermissions.permission_create ||
            userPermissions.ref_no_setting
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
            v-if="userPermissions.department_list || userPermissions.department_create"
          >
            <v-list-item-content>
              <v-list-item-title>Department</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/manager/index"
            v-if="userPermissions.manager_list || userPermissions.manager_create"
          >
            <v-list-item-content>
              <v-list-item-title>Manager</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/ref_no_setting/index"
            v-if="userPermissions.ref_no_setting"
          >
            <v-list-item-content>
              <v-list-item-title>Ref No. Settings</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/holiday/index"
            v-if="userPermissions.holiday_list || userPermissions.holiday_create"
          >
            <v-list-item-content>
              <v-list-item-title>Holiday</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/role/index"
            v-if="userPermissions.role_list || userPermissions.role_create"
          >
            <v-list-item-content>
              <v-list-item-title>Role</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/permission/index"
            v-if="userPermissions.permission_list || userPermissions.permission_create"
          >
            <v-list-item-content>
              <v-list-item-title>Permission</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
        <v-list-item
          link
          to="/activity_logs"
          v-if="userPermissions.activity_logs"
        >
          <v-list-item-icon>
            <v-icon>mdi-history</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Activity Logs</v-list-item-title>
        </v-list-item>
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

import axios from "axios";
import { mapState, mapActions } from 'vuex';

export default {
  data() {
    return {
      absolute: true,
      overlay: false,
      drawer: true,
      mini: false,
      right: null,
      selectedItem: 1,
      loading: null,
      initiated: false,
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
        import_project_log: false,
        export_project_log: false,
        view_all_projects: false,
        edit_template_percentage: false,
        edit_program_percentage: false,
        edit_validate_percentage: false,
        endorse_project: false,
        endorse_history: false,
        project_acceptance_overview: false,
        project_acceptance_overview_delete: false,
        activity_logs: false,
      },
      roles: {
        administrator: false,
      },
      user_permissions: [],
      user_roles: [],
    };
  },

  methods: {
    
    logout() {
      this.overlay = true;
      axios.get("/api/auth/logout").then(
        (response) => {
          if (response.data.success) {
            this.overlay = false;
            localStorage.removeItem("access_token");
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
    
    websocket() {
      // window.Echo.channel("WebsocketChannel").listen("WebsocketEvent", (e) => {
      //   let action = e.data.action;

      //   if (
      //     action == "user-edit" ||
      //     action == "role-edit" ||
      //     action == "role-delete" ||
      //     action == "permission-delete"
      //   ) {
      //     this.userRolesPermissions();
      //   }
      // });

      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;
        if (
          action == "user-edit" ||
          action == "role-edit" ||
          action == "role-delete" ||
          action == "permission-create" ||
          action == "permission-delete"
        ) {
          this.userRolesPermissions();
        }
      };
    },

    ...mapActions('auth', ['getUser']),
    ...mapActions("userRolesPermissions", ["userRolesPermissions"]),
  },
  
  computed: {
    ...mapState('auth', { user: 'user_name' }),
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getUser();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>