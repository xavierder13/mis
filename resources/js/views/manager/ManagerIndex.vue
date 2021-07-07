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
              v-if="userPermissions.manager_list"
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
                  v-if="userPermissions.manager_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>

                <v-dialog v-model="dialog" max-width="500px" persistent>
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
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
            v-if="userPermissions.manager_list"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editManager(item)"
                v-if="userPermissions.manager_edit"
              >
                mdi-pencil
              </v-icon>
              <v-icon small color="red" @click="showConfirmAlert(item)" v-if="userPermissions.manager_delete">
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
      loading: true,
    };
  },

  methods: {
    getManager() {
      this.loading = true;
      axios.get("/api/manager/index").then((response) => {
        this.departments = response.data.departments;
        this.managers = response.data.managers;
        this.loading = false;
      }, (error) => {
        this.isUnauthorized(error);
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

      axios.post("/api/manager/delete", data).then(
        (response) => {
          // console.log(response.data);
          if(response.data.success)
          {
            // send data to Socket.IO Server
            this.$socket.emit("sendData", {action: 'manager-delete'});
          }
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

          const manager_id = item.id;
          const index = this.managers.indexOf(item);

          //Call delete Manager function
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

          axios.post("/api/manager/update/" + manager_id, data).then(
            (response) => {
              if (response.data.success) {

                // send data to Socket.IO Server
                this.$socket.emit("sendData", {action: 'manager-update'});

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
              this.isUnauthorized(error);
              this.overlay = false;
              this.disabled = false;
            }
          );
        } else {
          const data = this.editedItem;

          axios.post("/api/manager/store", data).then(
            (response) => {
 
              if (response.data.success) {

                // send data to Socket.IO Server
                this.$socket.emit("sendData", {action: 'manager-create'});

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
              this.isUnauthorized(error);
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
        
        if(action == 'manager-create' || action == 'manager-edit' || action == 'manager-delete')
        {
          this.getManager();
        }
      }
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
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");

    this.getManager();
    this.websocket();
  },
};
</script>