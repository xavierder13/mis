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
            Manager Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
              v-if="permissions.manager_list"
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
                  v-if="permissions.manager_create"
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
                              name="department"
                              v-model="editedItem.name"
                              label="Name"
                              required
                              :error-messages="nameErrors"
                              @input="$v.editedItem.name.$touch()"
                              @blur="$v.editedItem.name.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-autocomplete
                              name="department"
                              v-model="editedItem.department_id"
                              :items="departments"
                              item-value="id"
                              item-text="name"
                              label="Department"
                              required
                              :error-messages="departmentErrors"
                              @change="
                                $v.editedItem.department_id.$touch() +
                                  departmentOnChange()
                              "
                              @blur="$v.editedItem.department_id.$touch()"
                            ></v-autocomplete>
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
            :items="managers"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="permissions.manager_list"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editManager(item)"
                v-if="permissions.manager_edit"
              >
                mdi-pencil
              </v-icon>
              <v-icon small color="red" @click="showConfirmAlert(item)" v-if="permissions.manager_delete">
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
import Home from '../Home.vue';  

export default {

  components: {
    Home
  },

  mixins: [validationMixin],

  validations: {
    editedItem: {
      name: { required },
      department_id: { required },
    },
  },
  data() {
    return {
      absolute: true,
      overlay: false,
      switch1: true,
      search: "",
      headers: [
        { text: "Manager", value: "name" },
        { text: "Department", value: "department" },
        { text: "Active", value: "active" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      disabled: false,
      dialog: false,
      departments: [],
      managers: [],
      editedIndex: -1,
      editedItem: {
        name: "",
        department_id: "",
        department: "",
        active: "Y",
      },
      defaultItem: {
        name: "",
        department_id: "",
        department: "",
        active: "Y",
      },
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/dashboard",
        },
        {
          text: "Manager List",
          disabled: true,
        },
      ],
      permissions: Home.data().permissions,
      loading: true,
    };
  },

  methods: {
    getManager() {
      this.loading = true;
      Axios.get("/api/manager/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then((response) => {
        this.departments = response.data.departments;
        this.managers = response.data.managers;
        this.loading = false;
      }, (error) => {
        // if unauthenticated (401)
        if(error.response.status == '401')
        {
          localStorage.removeItem('access_token');
          this.$router.push({name: 'login'});
        }
      });
    },

    editManager(item) {
      this.editedIndex = this.managers.indexOf(item);
      this.editedItem = Object.assign({}, item);
      if (this.editedItem.active == "Y") {
        this.switch1 = true;
      } else {
        this.switch1 = false;
      }
      this.dialog = true;
    },

    deleteManager(manager_id) {
      const data = { manager_id: manager_id };

      Axios.post("/api/manager/delete", data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          console.log(response.data);
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

          const manager_id = item.id;
          const index = this.managers.indexOf(item);

          //Call delete Patient function
          this.deleteManager(manager_id);

          //Remove item from array services
          this.managers.splice(index, 1);

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
        this.overlay = true;
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedItem;
          const manager_id = this.editedItem.id;

          Axios.post("/api/manager/update/" + manager_id, data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              if (response.data.success) {
                Object.assign(
                  this.managers[this.editedIndex],
                  response.data.manager
                );
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
          const data = this.editedItem;

          Axios.post("/api/manager/store", data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              console.log(response.data);
              if (response.data.success) {
                this.disabled = false;
                this.showAlert();
                this.close();

                //push recently added data from database
                this.managers.push(response.data.manager);
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
      this.editedItem.name = "";
      this.editedItem.department = "";
      this.editedItem.department_id = "";
      this.editedItem.active = "Y";
      this.switch1 = true;
    },
    departmentOnChange() {
      let department_id = this.editedItem.department_id;

      for (let [key, val] of this.departments.entries()) {
        if (department_id == val.id) {
          this.editedItem.department = val.name;
        }
      }
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
      this.permissions.manager_list = Home.methods.hasPermission([
        "manager-list",
      ]);
      this.permissions.manager_create = Home.methods.hasPermission([
        "manager-create",
      ]);
      this.permissions.manager_edit = Home.methods.hasPermission([
        "manager-edit",
      ]);
      this.permissions.manager_delete = Home.methods.hasPermission([
        "manager-delete",
      ]);

      // hide column actions if user has no permission
      if (!this.permissions.manager_edit && !this.permissions.manager_delete) {
        this.headers[3].align = " d-none";
      }
      else
      {
        this.headers[3].align = "";
      }

      // if user is not authorize
      if (!this.permissions.manager_list && !this.permissions.manager_create) {
        this.$router.push("/unauthorize").catch(() => {});
      }
      
    },
    websocket() {
      window.Echo.channel("WebsocketChannel").listen("WebsocketEvent", (e) => {
        let action = e.data.action;
  
        if (
          action == "user-edit" ||
          action == "role-edit" ||
          action == "role-delete" ||
          action == "permission-delete"
        ) {

          this.userRolesPermissions();
        }

        if(action == 'manager-create' || action == 'manager-edit' || action == 'manager-delete')
        {
          this.getManager();
        }

      });
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Manager" : "Edit Manager";
    },
    nameErrors() {
      const errors = [];
      if (!this.$v.editedItem.name.$dirty) return errors;
      !this.$v.editedItem.name.required && errors.push("Name is required.");
      return errors;
    },
    departmentErrors() {
      const errors = [];
      if (!this.$v.editedItem.department_id.$dirty) return errors;
      !this.$v.editedItem.department_id.required &&
        errors.push("Department is required.");
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
    access_token = localStorage.getItem("access_token");

    this.getManager();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>