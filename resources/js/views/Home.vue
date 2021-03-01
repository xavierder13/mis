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
    };
  },

  methods: {
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
        }
      );
    },
  },

  mounted() {
    access_token = localStorage.getItem("access_token");
  },
};
</script>