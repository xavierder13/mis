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
            Department Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              v-if="userPermissions.department_list"
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
                  v-if="userPermissions.department_create"
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
                              v-model="editedItem.department"
                              label="Department"
                              required
                              :error-messages="
                                departmentIsTaken
                                  ? departmentError
                                  : departmentErrors
                              "
                              @input="
                                $v.editedItem.department.$touch() +
                                  (departmentIsTaken = false) +
                                  (departmentError = '')
                              "
                              @blur="$v.editedItem.department.$touch()"
                            ></v-text-field>
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
            :items="departments"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="userPermissions.department_list"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editDepartment(item)"
                v-if="userPermissions.department_edit"
              >
                mdi-pencil
              </v-icon>

              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="userPermissions.department_delete"
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
import { mapState } from "vuex";

export default {
  mixins: [validationMixin],

  validations: {
    editedItem: {
      department: { required },
    },
  },
  data() {
    return {
      absolute: true,
      overlay: false,
      switch1: true,
      search: "",
      headers: [
        { text: "Department", value: "name" },
        { text: "Active", value: "active" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      disabled: false,
      dialog: false,
      departments: [],
      editedIndex: -1,
      editedItem: {
        department: "",
        active: "Y",
      },
      defaultItem: {
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
          text: "Department Lists",
          disabled: true,
        },
      ],
      loading: true,
      departmentIsTaken: false,
      departmentError: "",
    };
  },

  methods: {
    getDepartment() {
      this.loading = true;
      axios.get("/api/department/index").then(
        (response) => {
          this.departments = response.data.departments;
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editDepartment(item) {
      this.editedIndex = this.departments.indexOf(item);
      this.editedItem.id = item.id;
      this.editedItem.department = item.name;
      this.editedItem.active = item.active;
      if (this.editedItem.active == "Y") {
        this.switch1 = true;
      } else {
        this.switch1 = false;
      }
      this.dialog = true;
    },

    deleteDepartment(department_id) {
      const data = { department_id: department_id };

      axios.post("/api/department/delete", data).then(
        (response) => {
          // console.log(response.data);
          if (response.data.success) {
            // send data to Socket.IO Server
            this.$socket.emit("sendData", { action: "department-delete" });
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

          const department_id = item.id;
          const index = this.departments.indexOf(item);

          //Call delete Department function
          this.deleteDepartment(department_id);

          //Remove item from array services
          this.departments.splice(index, 1);

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
          const department_id = this.editedItem.id;

          axios.post("/api/department/update/" + department_id, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "department-edit" });

                Object.assign(
                  this.departments[this.editedIndex],
                  response.data.department
                );
                this.showAlert();
                this.close();
              } else if (response.data.department) {
                this.departmentIsTaken = true;
                this.departmentError = "Department already exists";
              }
              this.overlay = false;
              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.overlay = false;
            }
          );
        } else {
          const data = this.editedItem;

          axios.post("/api/department/store", data).then(
            (response) => {
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "department-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.departments.push(response.data.department);
              } else if (response.data.department) {
                this.departmentIsTaken = true;
                this.departmentError = "Department already exists";
              }
              this.overlay = false;
              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.overlay = false;
            }
          );
        }
      }
    },
    clear() {
      this.$v.$reset();
      this.editedItem.department = "";
      this.editedItem.active = "Y";
      this.switch1 = true;
      this.departmentError = "";
      this.departmentIsTaken = false;
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
          action == "department-create" ||
          action == "department-edit" ||
          action == "department-delete"
        ) {
          this.getDepartment();
        }
      };
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Department" : "Edit Department";
    },
    departmentErrors() {
      const errors = [];
      if (!this.$v.editedItem.department.$dirty) return errors;
      !this.$v.editedItem.department.required &&
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

    this.getDepartment();
    this.websocket();
  },
};
</script>