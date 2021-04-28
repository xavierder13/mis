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
            Permission Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
              v-if="user_permissions.permission_list"
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
                  v-if="user_permissions.permission_create"
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
                          <v-col>
                            <v-text-field
                              name="permission"
                              v-model="editedPermission.name"
                              label="Permission"
                              required
                              :error-messages="permissionErrors"
                              @input="$v.editedPermission.name.$touch()"
                              @blur="$v.editedPermission.name.$touch()"
                            ></v-text-field>
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
                        class="mb-4 mr-4"
                        :disabled="disabled"
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
            :items="permissions"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="user_permissions.permission_list"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editPermission(item)"
                v-if="user_permissions.permission_edit"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="user_permissions.permission_delete"
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
    editedPermission: {
      name: { required },
    },
  },
  data() {
    return {
      search: "",
      headers: [
        { text: "Permission", value: "name" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      disabled: false,
      dialog: false,
      permissions: [],
      user_permissions: Home.data().permissions,
      editedIndex: -1,
      editedPermission: {
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
          text: "Permission Lists",
          disabled: true,
        },
      ],
      loading: true,
    };
  },

  methods: {
    getPermission() {
      this.loading = true;
      Axios.get("/api/permission/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then((response) => {
        this.permissions = response.data.permissions;
        this.loading = false;
      });
    },

    editPermission(item) {
      this.editedIndex = this.permissions.indexOf(item);
      this.editedPermission = Object.assign({}, item);
      this.dialog = true;
    },

    deletePermission(permissionid) {
      const data = { permissionid: permissionid };
      this.loading = true;
      Axios.post("/api/permission/delete", data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {

          if(response.data.success)
          {
            // send data to Sockot.IO Server
            this.$socket.emit("sendData", {action: 'permission-delete'});
          }
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

          const permissionid = item.id;
          const index = this.permissions.indexOf(item);

          //Call delete Patient function
          this.deletePermission(permissionid);

          //Remove item from array permissions
          this.permissions.splice(index, 1);

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
        this.editedPermission = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedPermission;
          const permissionid = this.editedPermission.id;

          Axios.post("/api/permission/update/" + permissionid, data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {

              if (response.data.success) {

                // send data to Sockot.IO Server
                this.$socket.emit("sendData", {action: 'permission-edit'});

                Object.assign(
                  this.permissions[this.editedIndex],
                  this.editedPermission
                );
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
              }

              this.disabled = false;
            },
            (error) => {
              console.log(error);
            }
          );
        } else {
          const data = this.editedPermission;

          Axios.post("/api/permission/store", data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {

              if (response.data.success) {

                // send data to Sockot.IO Server
                this.$socket.emit("sendData", {action: 'permission-create'});

                this.disabled = false;
                this.showAlert();
                this.close();

                //push recently added data from database
                this.permissions.push(response.data.permission);
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
      this.editedPermission.name = "";
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
      this.user_permissions.permission_list = Home.methods.hasPermission([
        "permission-list",
      ]);
      this.user_permissions.permission_create = Home.methods.hasPermission([
        "permission-create",
      ]);
      this.user_permissions.permission_edit = Home.methods.hasPermission([
        "permission-edit",
      ]);
      this.user_permissions.permission_delete = Home.methods.hasPermission([
        "permission-delete",
      ]);

      // hide column actions if user has no permission
      if (
        !this.user_permissions.permission_edit &&
        !this.user_permissions.permission_delete
      ) {
        this.headers[1].align = " d-none";
      }
      else
      {
        this.headers[1].align = "";
      }

      // if user is not authorize
      if (
        !this.user_permissions.permission_list &&
        !this.user_permissions.permission_create
      ) {
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

      //   if(action == 'permission-create' || action == 'permission-edit' || action == 'permission-delete')
      //   {
      //     this.getPermission();
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

        if(action == 'permission-create' || action == 'permission-edit' || action == 'permission-delete')
        {
          this.getPermission();
        }
      }
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Permission" : "Edit Permission";
    },
    permissionErrors() {
      const errors = [];
      if (!this.$v.editedPermission.name.$dirty) return errors;
      !this.$v.editedPermission.name.required &&
        errors.push("Permission is required.");
      return errors;
    },
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getPermission();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>