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
          <v-card-title class="mb-0 pb-0">
            <span class="headline">Create User</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="ml-4">
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="name"
                  v-model="editedItem.name"
                  :error-messages="nameErrors"
                  label="Full Name"
                  @input="$v.editedItem.name.$touch()"
                  @blur="$v.editedItem.name.$touch()"
                  :readonly="editedItem.id == 1 ? true : false"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="email"
                  v-model="editedItem.email"
                  :error-messages="emailErrors"
                  label="E-mail"
                  @input="$v.editedItem.email.$touch()"
                  @blur="$v.editedItem.email.$touch()"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="password"
                  v-model="editedItem.password"
                  :error-messages="passwordErrors"
                  label="Password"
                  required
                  @input="$v.password.$touch()"
                  @blur="$v.password.$touch()"
                  type="password"
                  :readonly="editedItem.id == 1 ? true : false"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="confirm_password"
                  v-model="editedItem.confirm_password"
                  :error-messages="confirm_passwordErrors"
                  label="Confirm Password"
                  required
                  @input="$v.confirm_password.$touch()"
                  @blur="$v.confirm_password.$touch()"
                  type="password"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-autocomplete
                  name="type"
                  v-model="editedItem.type"
                  :items="types"
                  item-value="value"
                  item-text="text"
                  label="User Type"
                  required
                  :error-messages="typeErrors"
                  @change="$v.editedItem.type.$touch()"
                  @blur="$v.editedItem.type.$touch()"
                  v-if="editedItem.id != 1"
                ></v-autocomplete>
                <v-text-field
                  name="name"
                  label="User Type"
                  v-model="editedItem.type"
                  readonly
                  v-if="editedItem.id == 1"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-autocomplete
                  v-model="editedItem.roles"
                  :items="roles"
                  item-text="name"
                  item-value="name"
                  label="Roles"
                  multiple
                  chips
                >
                  <template v-slot:selection="data">
                    <v-chip
                      color="secondary"
                      v-bind="data.attrs"
                      :input-value="data.selected"
                      @click="data.select"
                    >
                      {{ data.item.name }}
                    </v-chip>
                  </template>
                </v-autocomplete>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="2" class="mt-0 mb-0 pt-0 pb-0">
                <v-switch v-model="switch1" :label="activeStatus"></v-switch>
              </v-col>
            </v-row>
          </v-card-text>

          <v-card-actions>
            <v-btn
              color="primary"
              @click="save"
              :disabled="disabled"
              class="ml-6 mb-4 mr-1"
            >
              Save
            </v-btn>
            <v-btn color="#E0E0E0" to="/dashboard" class="mb-4"> Cancel </v-btn>
          </v-card-actions>
        </v-card>
      </v-main>
    </div>
  </div>
</template>
<script>
let access_token;
let user_permissions;
let user_roles;

import Axios from "axios";
import { validationMixin } from "vuelidate";
import {
  required,
  maxLength,
  email,
  minLength,
  sameAs,
} from "vuelidate/lib/validators";
import Home from "../Home.vue";

export default {
  components: {
    Home,
  },

  mixins: [validationMixin],

  validations: {
    editedItem: {
      name: { required },
      email: { required, email },
      password: { required, minLength: minLength(8) },
      confirm_password: { required, sameAsPassword: sameAs("password") },
      type: { required },
    },
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
          text: "Create User",
          disabled: true,
        },
      ],
      switch1: true,
      disabled: false,
      users: [],
      roles: [],
      roles_permissions: [],
      permissions: Home.data().permissions,
      types: [
        { text: "Programmer", value: "Programmer" },
        { text: "Validator", value: "Validator" },
      ],
      editedIndex: -1,
      editedItem: {
        name: "",
        email: "",
        password: "",
        confirm_password: "",
        type: "",
        roles: [],
        active: "Y",
      },
      defaultItem: {
        name: "",
        email: "",
        password: "",
        confirm_password: "",
        type: "",
        roles: [],
        active: "Y",
      },
      permissions: {
        user_list: false,
        user_create: false,
        user_edit: false,
        user_delete: false,
      },
      user_permissions: [],
      user_roles: [],
    };
  },

  methods: {
    getRole() {
      Axios.get("/api/user/create").then(
        (response) => {
          this.roles = response.data.roles;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    showAlert() {
      this.$swal({
        position: "center",
        icon: "success",
        title: "Record has been saved",
        showConfirmButton: false,
        timer: 2500,
      });
    },

    save() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.disabled = true;
        this.overlay = true;

        this.editedItem.password = this.password;
        this.editedItem.confirm_password = this.confirm_password;

        const data = this.editedItem;

        Axios.post("/api/user/store", data).then(
          (response) => {
            if (response.data.success) {
              // send data to Sockot.IO Server
              this.$socket.emit("sendData", { action: "user-create" });

              this.showAlert();
              this.clear();

              //push recently added data from database
              this.users.push(response.data.user);
            }
            this.overlay = false;
            this.disabled = false;
          },
          (error) => {
            this.isUnauthorized(error);

            this.overlay = false;
            this.disabled = false;
          }
        );
      }
    },
    clear() {
      this.$v.$reset();
      this.editedItem = Object.assign({}, this.defaultItem);
      this.switch1 = true;
    },

    userRolesPermissions() {
      Axios.get("api/user/roles_permissions").then(
        (response) => {
          this.user_permissions = response.data.user_permissions;
          this.user_roles = response.data.user_roles;
          this.getRolesPermissions();
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

    getRolesPermissions() {
      this.permissions.user_create = this.hasPermission(["user-create"]);

      // if user is not authorize
      if (!this.permissions.user_create) {
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
      };
    },
  },
  computed: {
    nameErrors() {
      const errors = [];
      if (!this.$v.editedItem.name.$dirty) return errors;
      !this.$v.editedItem.name.required && errors.push("Name is required.");
      return errors;
    },
    emailErrors() {
      const errors = [];
      if (!this.$v.editedItem.email.$dirty) return errors;
      !this.$v.editedItem.email.required && errors.push("Email is required.");
      !this.$v.editedItem.email.email && errors.push("Must be valid e-mail");
      return errors;
    },
    passwordErrors() {
      const errors = [];
      if (!this.$v.editedItem.password.$dirty) return errors;
      !this.$v.editedItem.password.required &&
        errors.push("Password is required.");
      !this.$v.editedItem.password.minLength &&
        errors.push("Password must be atleast 8 characters.");
      return errors;
    },

    confirm_passwordErrors() {
      const errors = [];
      if (!this.$v.editedItem.confirm_password.$dirty) return errors;
      !this.$v.confirm_password.required &&
        errors.push("Password Confirmation is required.");
      !this.$v.editedItem.confirm_password.sameAsPassword &&
        errors.push("Passwords did not match");
      return errors;
    },

    typeErrors() {
      const errors = [];
      if (!this.$v.editedItem.type.$dirty) return errors;
      !this.$v.editedItem.type.required &&
        errors.push("User type is required.");
      return errors;
    },

    activeStatus() {
      if (this.switch1) {
        this.editedItem.active = "Y";
        return " Active";
      } else {
        this.editedItem.active = "N";
        return " Inactive";
      }
    },
  },
  mounted() {
    Axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getRole();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>