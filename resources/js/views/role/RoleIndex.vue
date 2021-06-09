<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
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
            Roles Record
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
              v-if="user_permissions.role_list"
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
                  v-if="user_permissions.role_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>
                <v-dialog v-model="dialog" max-width="1200px" persistent>
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col>
                            <v-text-field
                              name="role"
                              v-model="editedRole.name"
                              label="Role"
                              required
                              :error-messages="roleErrors"
                              @input="$v.editedRole.name.$touch()"
                              @blur="$v.editedRole.name.$touch()"
                              :readonly="role.id != 1 ? true : ''"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row class="pa-2">
                          <template v-for="item in permissions">
                            <v-col cols="2" class="pa-0 ma-0">
                              <v-checkbox
                                v-model="permission"
                                :label="item.name"
                                :value="item.id"
                                class="pa-0 ma-0"
                                :readonly="role.id != 1 ? true : ''"
                              ></v-checkbox>
                            </v-col>
                          </template>
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
                        class="mb-4 mr-4"
                        :disabled="disabled"
                        v-if="role.id != 1"
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
          <v-data-table
            :headers="headers"
            :items="roles"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="user_permissions.role_list"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon small class="mr-2" color="green" @click="editRole(item)" v-if="user_permissions.role_edit && item.name != 'Administrator'">
                mdi-pencil
              </v-icon>
              <v-icon small color="red" @click="showConfirmAlert(item)" v-if="user_permissions.role_delete && item.name != 'Administrator'">
                mdi-delete
              </v-icon>
              <v-icon small color="info" @click="editRole(item)" v-if="item.name == 'Administrator'">
                mdi-eye
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
import { required, maxLength, email } from "vuelidate/lib/validators";
import Home from "../Home.vue";

export default {
  components: {
    Home,
  },

  mixins: [validationMixin],

  validations: {
    editedRole: {
      name: { required },
    },
  },
  data() {
    return {
      search: "",
      headers: [
        { text: "Role", value: "name" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      disabled: false,
      dialog: false,
      permission: [],
      permissions: [],
      user_permissions: Home.data().permissions,
      use_roles: {
        administrator: false,
      },
      roles: [],
      role: [],
      editedIndex: -1,
      editedRole: {
        name: "",
      },
      defaultItem: {
        name: "",
      },
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/dashboard",
        },
        {
          text: "Roles Record",
          disabled: true,
        },
      ],
      loading: true,
    };
  },

  methods: {
    getPermission() {
      Axios.get("/api/permission/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then((response) => {
        this.permissions = response.data.permissions;
      });
    },

    getRole() {
      this.loading = true;
      Axios.get("/api/role/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then((response) => {
        this.roles = response.data.roles;
        this.loading = false;
      });
    },

    editRole(item) {
      const data = { roleid: item.id };

      this.role = item;

      Axios.post("/api/role/edit", data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          this.permission = Object.values(response.data.rolePermissions);
          this.editedIndex = this.roles.indexOf(item);
          this.editedRole = Object.assign({}, item);
          this.dialog = true;
        },
        (error) => {
          console.log(error);
        }
      );
    },

    deleteRole(roleid) {
      const data = { roleid: roleid };
      this.loading = true;
      Axios.post("/api/role/delete", data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          this.loading = false;
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

          const roleid = item.id;
          const index = this.roles.indexOf(item);

          //Call delete Patient function
          this.deleteRole(roleid);

          //Remove item from array permissions
          this.roles.splice(index, 1);

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
        this.editedRole = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = {
            name: this.editedRole.name,
            permission: this.permission,
          };
          const roleid = this.editedRole.id;

          Axios.post("/api/role/update/" + roleid, data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              if (response.data.success) {

                // send data to Sockot.IO Server
                this.$socket.emit("sendData", {action: 'role-edit'});

                Object.assign(this.roles[this.editedIndex], this.editedRole);
                this.showAlert();
                this.close();

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
              }

              this.disabled = false;
            },
            (error) => {
              console.log(error);
            }
          );
        } else {
          const data = {
            name: this.editedRole.name,
            permission: this.permission,
          };

          Axios.post("/api/role/store", data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              if (response.data.success) {

                // send data to Sockot.IO Server
                this.$socket.emit("sendData", {action: 'role-create'});

                this.disabled = false;
                this.showAlert();
                this.close();

                //push recently added data from database
                this.roles.push(response.data.role);
              }
            },
            (error) => {
              console.log(error);
            }
          );
        }
      }
    },
    clear() {
      this.$v.$reset();
      this.editedRole.name = "";
      this.permission = [];
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
      this.user_permissions.role_list = Home.methods.hasPermission([
        "role-list",
      ]);
      this.user_permissions.role_create = Home.methods.hasPermission([
        "role-create",
      ]);
      this.user_permissions.role_edit = Home.methods.hasPermission([
        "role-edit",
      ]);
      this.user_permissions.role_delete = Home.methods.hasPermission([
        "role-delete",
      ]);

      this.roles.administrator = Home.methods.hasRole(["Administrator"]);

      // hide column actions if user has no permission
      if (
        !this.user_permissions.role_edit &&
        !this.user_permissions.role_delete
      ) {
        this.headers[1].align = " d-none";
      }
      else
      {
        this.headers[1].align = "";
      }

      // if user is not authorize
      if (
        !this.user_permissions.role_list &&
        !this.user_permissions.role_create
      ) {
        this.$router.push("/unauthorize").catch(() => {});
      }
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
      }
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Role" : "Edit Role";
    },
    roleErrors() {
      const errors = [];
      if (!this.$v.editedRole.name.$dirty) return errors;
      !this.$v.editedRole.name.required && errors.push("Role is required.");
      return errors;
    },
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getPermission();
    this.getRole();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>