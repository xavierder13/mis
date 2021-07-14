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
              v-if="userPermissions.role_list"
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
                  v-if="userPermissions.role_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>
                <v-dialog v-model="dialog" max-width="1000px" persistent>
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
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
                              :readonly="role.id == 1 ? true : false"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <!-- <v-row class="pa-2">
                          <template v-for="item in permissions">
                            <v-col cols="2" class="pa-0 ma-0">
                              <v-checkbox
                                v-model="permission"
                                :label="item.name"
                                :value="item.id"
                                class="pa-0 ma-0"
                                :readonly="role.id == 1 ? true : false"
                              ></v-checkbox>
                            </v-col>
                          </template>
                        </v-row> -->
                        <v-row>
                          <v-col>
                            <v-autocomplete
                              v-model="permission"
                              :items="permissions"
                              item-text="name"
                              item-value="id"
                              label="Permissions"
                              multiple
                              chips
                              :readonly="role.id == 1 ? true : false"
                              :clearable="role.id != 1 ? true : false"
                            >
                              <template v-slot:selection="data">
                                <v-chip
                                  color="secondary"
                                  v-bind="data.attrs"
                                  :input-value="data.selected"
                                  :close="role.id != 1 ? true : false"
                                  @click="data.select"
                                  @click:close="removePermission(data.item)"
                                >
                                  {{ data.item.name }}
                                </v-chip>
                              </template>
                            </v-autocomplete>
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
            v-if="userPermissions.role_list"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-1"
                color="primary"
                @click="editRole(item)"
                v-if="item.name == 'Administrator'"
              >
                mdi-eye
              </v-icon>
              <v-icon
                small
                class="mr-1"
                color="green"
                @click="editRole(item)"
                v-if="userPermissions.role_edit && item.name != 'Administrator'"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="
                  userPermissions.role_delete && item.name != 'Administrator'
                "
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

import axios from "axios";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";
import { mapState } from 'vuex';
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
      dialogPermission: false,

    };
  },

  methods: {
    getRole() {
      this.loading = true;
      axios.get("/api/role/index").then(
        (response) => {
          this.roles = response.data.roles;
          this.permissions = response.data.permissions;
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editRole(item) {
      const data = { roleid: item.id };
      let rolePermissions = item.permissions;
      this.role = item;
      this.permission = [];

      rolePermissions.forEach((value, index) => {
        this.permission.push(value.id);
      });

      this.editedIndex = this.roles.indexOf(item);
      this.editedRole = Object.assign({}, item);
      this.dialog = true;
    },

    deleteRole(roleid) {
      const data = { roleid: roleid };
      this.loading = true;
      axios.post("/api/role/delete", data).then(
        (response) => {
          this.loading = false;
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

          //Call delete Role function
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
        let permission = [];

        const data = {
          name: this.editedRole.name,
          permission: this.permission,
        };

        if (this.editedIndex > -1) {
          const roleid = this.editedRole.id;

          axios.post("/api/role/update/" + roleid, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                this.$socket.emit("sendData", { action: "role-edit" });

                Object.assign(this.roles[this.editedIndex], response.data.role);
                this.showAlert();
                this.close();

              }

              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.disabled = false;
            }
          );
        } else {
          axios.post("/api/role/store", data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                // this.$socket.emit("sendData", { action: "role-create" });
                this.showAlert();
                this.close();

                //push recently added data from database
                this.roles.push(response.data.role);
              }
              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.disabled = false;
            }
          );
        }
      }
    },
    clear() {
      this.$v.$reset();
      this.editedRole.name = "";
      this.permission = [];
      this.role = [];
    },

    viewPermission(item) {
      this.dialogPermission = true;
      this.role = item;
    },

    removePermission(item) {
      const index = this.permission.indexOf(item.id);
      if (index >= 0) this.permission.splice(index, 1);
    },

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },
    
    websocket() {
      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;

        if (
          action == "role-create" ||
          action == "role-edit" ||
          action == "role-delete"
        ) {
          this.getRole();
        }

      };
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1
        ? "New Role"
        : this.editedRole.name == "Administrator"
        ? "Role"
        : "Edit Role";
    },
    roleErrors() {
      const errors = [];
      if (!this.$v.editedRole.name.$dirty) return errors;
      !this.$v.editedRole.name.required && errors.push("Role is required.");
      return errors;
    },
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getRole();
    this.websocket();
  },
};
</script>