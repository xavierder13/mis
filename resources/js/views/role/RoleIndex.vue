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
            ></v-text-field>
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <v-dialog v-model="dialog" max-width="1200px">
                  <template v-slot:activator="{ on, attrs }">
                    <v-btn
                      color="primary"
                      fab
                      dark
                      class="mb-2"
                      v-bind="attrs"
                      v-on="on"
                      @click="clear()"
                    >
                      <v-icon>mdi-plus</v-icon>
                    </v-btn>
                  </template>
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
          >
            <template v-slot:item.actions="{ item }">
              <v-icon small class="mr-2" color="green" @click="editRole(item)">
                mdi-pencil
              </v-icon>
              <v-icon small color="red" @click="showConfirmAlert(item)">
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
import { required, maxLength, email } from "vuelidate/lib/validators";

export default {
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
      roles: [],
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

    getRolesPermissions() {
      user_permissions = JSON.parse(localStorage.getItem("user_permissions"));
      user_roles = JSON.parse(localStorage.getItem("user_roles"));
      this.Auth = {
        is: function is(role) {
          return user_roles.includes(roles, role);
        },

        can: function can(permission) {
          return user_permissions.includes(permission);
        },
      };
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
        //PUSHER - refresh data when table services has changes
        if (
          data.action == "create-role" ||
          data.action == "edit-role" ||
          data.action == "delete-role"
        ) {
          this.getRole();
        }

        if (
          data.action == "create-permission" ||
          data.action == "edit-permission" ||
          data.action == "delete-permission"
        ) {
          this.getPermission();
        }
      });
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
    this.getRolesPermissions();
    this.pusher();
  },
};
</script>