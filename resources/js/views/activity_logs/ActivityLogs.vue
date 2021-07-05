<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
      <v-overlay :absolute="absolute" :value="overlay">
        <v-progress-circular
          :size="70"
          :width="7"
          color="primary"
          indeterminate
        ></v-progress-circular>
      </v-overlay>
      <v-main>
        <v-breadcrumbs :items="items">
          <template v-slot:item="{ item }">
            <v-breadcrumbs-item :to="item.link" :disabled="item.disabled">
              {{ item.text.toUpperCase() }}
            </v-breadcrumbs-item>
          </template>
        </v-breadcrumbs>
        <v-card>
          <v-card-title>
            Activity Logs
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
              v-if="permissions.user_list"
            ></v-text-field>
          </v-card-title>
          <v-data-table
            :headers="headers"
            :items="activity_logs"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="permissions.activity_logs"
          >
          </v-data-table>
        </v-card>
      </v-main>
    </div>
  </div>
</template>
<script>
let access_token;
let user_permissions;
let user_roles;

import axios from "axios";
import Home from "../Home.vue";

export default {
  components: {
    Home,
  },

  data() {
    return {
      absolute: true,
      overlay: false,
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/dashboard",
        },
        {
          text: "Activity Logs",
          disabled: true,
        },
      ],
      search: "",
      headers: [
        { text: "Table", value: "log_name" },
        { text: "Event", value: "description" },
        { text: "Date", value: "log_date" },
        { text: "Subject ID", value: "subject_id" },
        { text: "Data", value: "properties", width: "450px" },
        { text: "User Name", value: "name" },
        { text: "User Email", value: "email" },
      ],

      activity_logs: [],
      roles: [],
      roles_permissions: [],
      permissions: Home.data().permissions,
      permissions: {
        activity_logs: false,
      },
      loading: true,
      user_permissions: [],
      user_roles: [],
    };
  },

  methods: {
    getActivityLogs() {
      this.loading = true;
      axios.get("/api/activity_logs/index").then(
        (response) => {
          this.activity_logs = response.data.activity_logs;
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },

    userRolesPermissions() {
      axios.get("api/user/roles_permissions").then((response) => {
        this.user_permissions = response.data.user_permissions;
        this.user_roles = response.data.user_roles;
        this.getRolesPermissions();
      });
    },

    getRolesPermissions() {
      this.permissions.activity_logs = this.hasPermission([
        "activity-logs",
      ]);

      // if user is not authorize
      if (!this.permissions.activity_logs) {
        this.$router.push("/unauthorize").catch(() => {});
      }
    },
     hasRole(roles) {
      let hasRole = false;

      roles.forEach((value, index) => {
        hasRole = this.user_roles.includes(value);
      });

      return hasRole;
    },

    hasPermission(permissions) {
      let hasPermission = false;

      permissions.forEach((value, index) => {
        hasPermission = this.user_permissions.includes(value);
      });

      return hasPermission;
    },
    websocket() {
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

        this.getActivityLogs();
      };
    },
  },
  computed: {},
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getActivityLogs();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>