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
            User Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
              v-if="permissions.user_list"
            ></v-text-field>
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="clear() + (dialog = true)"
                  v-if="permissions.user_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>

                <v-dialog v-model="dialog" max-width="500px" persistent>
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="name"
                              v-model="editedItem.name"
                              :error-messages="nameErrors"
                              label="Full Name"
                              @input="$v.editedItem.name.$touch()"
                              @blur="$v.editedItem.name.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="email"
                              v-model="editedItem.email"
                              :error-messages="emailErrors"
                              label="E-mail"
                              @input="$v.editedItem.email.$touch()"
                              @blur="$v.editedItem.email.$touch()"
                              :readonly="emailReadonly"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="password"
                              v-model="password"
                              :error-messages="passwordErrors"
                              label="Password"
                              required
                              @input="$v.password.$touch()"
                              @blur="$v.password.$touch() + dummyPassword"
                              @keyup="passwordChange()"
                              @focus="onFocus()"
                              type="password"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="confirm_password"
                              v-model="confirm_password"
                              :error-messages="confirm_passwordErrors"
                              label="Confirm Password"
                              required
                              @input="$v.confirm_password.$touch()"
                              @blur="
                                $v.confirm_password.$touch() + dummyPassword
                              "
                              @keyup="passwordChange()"
                              @focus="onFocus()"
                              type="password"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
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
                              v-if="editedItem.type == 'Admin'"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-combobox
                              v-model="editedItem.roles"
                              :items="roles"
                              item-text="name"
                              item-value="id"
                              label="Roles"
                              multiple
                              chips
                            ></v-combobox>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="2" class="mt-0 mb-0 pt-0 pb-0">
                            <v-switch
                              v-model="switch1"
                              :label="activeStatus"
                            ></v-switch>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>

                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn color="#E0E0E0" @click="close" class="mb-4">
                        Cancel
                      </v-btn>
                      <v-btn
                        color="primary"
                        @click="save"
                        :disabled="disabled"
                        class="mb-4 mr-4"
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
                <v-dialog
                  v-model="dialogPermission"
                  max-width="700px"
                  persistent
                >
                  <v-card>
                    <v-card-title>
                      <span class="headline">Roles</span>
                      <v-spacer></v-spacer>
                      <v-icon @click="dialogPermission = false"
                        >mdi-close</v-icon
                      >
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-expansion-panels>
                              <v-expansion-panel
                                v-for="(role, i) in roles_permissions"
                                :key="i"
                              >
                                <v-expansion-panel-header>
                                  {{ role.name }}
                                </v-expansion-panel-header>
                                <v-expansion-panel-content>
                                  <v-chip
                                    color="secondary"
                                    v-for="(permission, i) in role.permissions"
                                    :key="i"
                                    class="ma-1"
                                  >
                                    {{ permission.name }}
                                  </v-chip>
                                </v-expansion-panel-content>
                              </v-expansion-panel>
                            </v-expansion-panels>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>
                    <v-card-actions>
                      <v-spacer></v-spacer>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
          <v-data-table
            :headers="headers"
            :items="users"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="permissions.user_list"
          >
            <template v-slot:item.roles="{ item }">
              <span v-for="(role, key) in item.roles">
                <v-chip
                  small
                  color="secondary"
                  v-if="key == 0"
                  @click="viewRoles(item.roles)"
                >
                  {{ role.name }}
                </v-chip>

                <v-chip
                  small
                  v-if="key == 0 && item.roles.length > 1"
                  @click="viewRoles(item.roles)"
                >
                  {{ "+" }}
                  {{
                    key == 0 && item.roles.length > 1
                      ? item.roles.length - 1
                      : role.name
                  }}
                  {{ "others" }}
                </v-chip>
              </span>
            </template>
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editUser(item)"
                v-if="permissions.user_edit"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="permissions.user_delete && item.email != 'admin@mis.ac'"
              >
                mdi-delete
              </v-icon>
            </template>
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
      type: { required },
    },
    password: { required, minLength: minLength(8) },
    confirm_password: { required, sameAsPassword: sameAs("password") },
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
          text: "Users Record",
          disabled: true,
        },
      ],
      search: "",
      headers: [
        { text: "Full Name", value: "name" },
        { text: "E-mail", value: "email" },
        { text: "Active", value: "active" },
        { text: "User Type", value: "type" },
        { text: "Last Login", value: "last_login" },
        { text: "Roles", value: "roles" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      switch1: true,
      disabled: false,
      emailReadonly: false,
      dialog: false,
      dialogPermission: false,
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
      password: "",
      confirm_password: "",
      permissions: {
        user_list: false,
        user_create: false,
        user_edit: false,
        user_delete: false,
      },
      loading: true,
      passwordHasChanged: false,
    };
  },

  methods: {
    getUser() {
      this.loading = true;
      Axios.get("/api/user/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          this.users = response.data.users;
          this.loading = false;
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

    getRole() {
      Axios.get("/api/role/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then((response) => {
        this.roles = response.data.roles;
      });
    },

    editUser(item) {
      this.editedIndex = this.users.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.dialog = true;
      this.emailReadonly = true;
      this.password = "password";
      this.confirm_password = "password";
      if (item.active == "Y") {
        this.switch1 = true;
      } else {
        this.switch1 = false;
      }
    },

    deleteUser(user_id) {
      const data = { user_id: user_id };

      Axios.post("/api/user/delete", data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          if (response.data.success) {
            // send data to Socket.IO Server
            this.$socket.emit("sendData", { action: "user-delete" });
          }
        },
        (error) => {
          console.log(error);
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

    showConfirmAlert(item) {
      this.$swal({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Delete record!",
      }).then((result) => {
        // <--

        if (result.value) {
          // <-- if confirmed

          const user_id = item.id;
          const index = this.users.indexOf(item);

          //Call delete User function
          this.deleteUser(user_id);

          //Remove item from array services
          this.users.splice(index, 1);

          this.$swal({
            position: "center",
            icon: "success",
            title: "Record has been deleted",
            showConfirmButton: false,
            timer: 2500,
          });
        }
      });
    },

    close() {
      this.dialog = false;
      this.clear();
      this.$nextTick(() => {
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.disabled = true;
        this.overlay = true;
        let roles = [];

        if (this.editedItem.roles.length) {
          this.editedItem.roles.forEach((value, index) => {
            roles.push(value.name);
          });
        }

        this.editedItem.roles = roles;

        if (this.editedIndex > -1) {
          if (this.passwordHasChanged) {
            this.editedItem.password = this.password;
            this.editedItem.confirm_password = this.confirm_password;
          }

          const data = this.editedItem;
          const user_id = this.editedItem.id;

          Axios.post("/api/user/update/" + user_id, data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "user-edit" });

                Object.assign(this.users[this.editedIndex], response.data.user);
                this.showAlert();
                this.close();
              }
              this.overlay = false;
              this.disabled = false;
            },
            (error) => {
              console.log(error);
              this.overlay = false;
              this.disabled = false;
            }
          );
        } else {
          this.editedItem.password = this.password;
          this.editedItem.confirm_password = this.confirm_password;

          const data = this.editedItem;

          Axios.post("/api/user/store", data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              console.log(response.data);
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "user-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.users.push(response.data.user);
              }
              this.overlay = false;
              this.disabled = false;
            },
            (error) => {
              console.log(error);
              this.overlay = false;
              this.disabled = false;
            }
          );
        }
      }
    },
    clear() {
      this.$v.$reset();
      this.editedItem.email = "";
      this.emailReadonly = false;
      this.password = "";
      this.confirm_password = "";
      this.editedItem.active = "Y";
      this.passwordHasChanged = false;
      this.switch1 = true;
    },
    onFocus() {
      if (this.editedIndex > -1) {
        if (!this.passwordHasChanged) {
          this.password = "";
          this.confirm_password = "";
        }
      }
    },
    passwordChange() {
      if (this.password || this.confirm_password) {
        this.passwordHasChanged = true;
      } else {
        this.passwordHasChanged = false;
      }
    },
    viewRoles(roles) {
      this.dialogPermission = true;
      this.roles_permissions = roles;
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
      this.permissions.user_list = Home.methods.hasPermission(["user-list"]);
      this.permissions.user_create = Home.methods.hasPermission([
        "user-create",
      ]);
      this.permissions.user_edit = Home.methods.hasPermission(["user-edit"]);
      this.permissions.user_delete = Home.methods.hasPermission([
        "user-delete",
      ]);

      // hide column actions if user has no permission
      if (!this.permissions.user_edit && !this.permissions.user_delete) {
        this.headers[6].align = " d-none";
      } else {
        this.headers[6].align = "";
      }

      // if user is not authorize
      if (!this.permissions.user_list && !this.permissions.user_create) {
        this.$router.push("/unauthorize").catch(() => {});
      }
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

      //   if(action == 'user-create' || action == 'user-edit' || action == 'user-delete' || action == 'login')
      //   {
      //     this.getUser();
      //   }

      // });

      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;
        if (
          action == "user-edit" ||
          action == "role-edit" ||
          action == "role-delete" ||
          action == "permission-delete"
        ) {
          this.userRolesPermissions();
        }

        if (
          action == "user-create" ||
          action == "user-edit" ||
          action == "user-delete" ||
          action == "login"
        ) {
          this.getUser();
        }
      };
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New User" : "Edit User";
    },
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
      if (!this.$v.password.$dirty) return errors;
      !this.$v.password.required && errors.push("Password is required.");
      !this.$v.password.minLength &&
        errors.push("Password must be atleast 8 characters.");
      return errors;
    },

    confirm_passwordErrors() {
      const errors = [];
      if (!this.$v.confirm_password.$dirty) return errors;
      !this.$v.confirm_password.required &&
        errors.push("Password Confirmation is required.");
      !this.$v.confirm_password.sameAsPassword &&
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
    dummyPassword() {
      if (this.editedIndex > -1) {
        if (!this.password && !this.confirm_password) {
          this.password = "password";
          this.confirm_password = "password";
        }
      }
    },
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getUser();
    this.getRole();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>