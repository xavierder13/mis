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
        <v-card>
          <v-card-title>
            Programmer Projects
            <v-divider
              vertical
              class="ml-3"
              v-if="permissions.import_project"
            ></v-divider>

            <v-icon
              color="primary"
              class="ml-2"
              @click="importExcel()"
              v-if="permissions.import_project"
            >
              mdi-import
            </v-icon>

            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
              v-if="permissions.project_list"
            ></v-text-field>
  
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="clear() + (dialog = true) + getRefNumber()"
                  v-if="permissions.project_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>

                <v-dialog v-model="dialog" max-width="700px">
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col cols="2" class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="ref_no"
                              v-model="editedItem.ref_no"
                              label="Reference #"
                              readonly
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="report_title"
                              v-model="editedItem.report_title"
                              label="Report Title"
                              required
                              :error-messages="report_titleErrors"
                              @input="$v.editedItem.report_title.$touch()"
                              @blur="$v.editedItem.report_title.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
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
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-autocomplete
                              name="programmer"
                              v-model="editedItem.programmer_id"
                              :items="programmers"
                              item-value="id"
                              item-text="name"
                              label="Programmer"
                              required
                              :error-messages="programmerErrors"
                              @change="
                                $v.editedItem.programmer_id.$touch() +
                                  programmerOnChange()
                              "
                              @blur="$v.editedItem.programmer_id.$touch()"
                              :readonly="
                                user_type == 'Programmer' ? true : false
                              "
                            ></v-autocomplete>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-autocomplete
                              name="validator"
                              v-model="editedItem.validator_id"
                              :items="validators"
                              item-value="id"
                              item-text="name"
                              label="Validator"
                            ></v-autocomplete>
                          </v-col>

                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-autocomplete
                              name="type"
                              v-model="editedItem.type"
                              :items="types"
                              item-value="value"
                              item-text="text"
                              label="Report Type"
                              required
                              :error-messages="typeErrors"
                              @change="$v.editedItem.type.$touch()"
                              @blur="$v.editedItem.type.$touch()"
                            ></v-autocomplete>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-menu
                              v-model="input_date_received"
                              :close-on-content-click="false"
                              transition="scale-transition"
                              offset-y
                              max-width="290px"
                              min-width="290px"
                            >
                              <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                  name="date_received"
                                  v-model="computedDateReceivedFormatted"
                                  label="Date Received"
                                  hint="MM/DD/YYYY format"
                                  persistent-hint
                                  prepend-icon="mdi-calendar"
                                  readonly
                                  v-bind="attrs"
                                  v-on="on"
                                ></v-text-field>
                              </template>
                              <v-date-picker
                                v-model="date_received"
                                no-title
                                @input="input_date_received = false"
                              ></v-date-picker>
                            </v-menu>
                          </v-col>
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-menu
                              v-model="input_date_approved"
                              :close-on-content-click="false"
                              transition="scale-transition"
                              offset-y
                              max-width="290px"
                              min-width="290px"
                            >
                              <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                  name="date_received"
                                  v-model="computedDateApprovedFormatted"
                                  label="Date Approved"
                                  hint="MM/DD/YYYY format"
                                  persistent-hint
                                  prepend-icon="mdi-calendar"
                                  readonly
                                  v-bind="attrs"
                                  v-on="on"
                                ></v-text-field>
                              </template>
                              <v-date-picker
                                v-model="date_approved"
                                no-title
                                @input="input_date_approved = false"
                              ></v-date-picker>
                            </v-menu>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="4" class="mt-1 mb-0 pt-0 pb-0">
                            <v-text-field-money
                              v-model="editedItem.ideal_prog_hrs"
                              v-bind:properties="{
                                name: 'ideal_prog_hrs',
                                placeholder: '0.00',
                              }"
                              v-bind:options="{
                                length: 8,
                                precision: 2,
                                empty: null,
                              }"
                              v-bind:label="'Ideal Programming Hrs.'"
                            >
                            </v-text-field-money>
                          </v-col>
                          <v-col cols="4" class="mt-1 mb-0 pt-0 pb-0">
                            <v-text-field-money
                              v-model="editedItem.ideal_valid_hrs"
                              v-bind:properties="{
                                name: 'ideal_valid_hrs',
                                placeholder: '0.00',
                              }"
                              v-bind:options="{
                                length: 8,
                                precision: 2,
                                empty: null,
                              }"
                              v-bind:label="'Ideal Validation Hrs.'"
                            >
                            </v-text-field-money>
                          </v-col>
                          <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field-money
                              class="mt-2"
                              v-model="editedItem.template_percent"
                              v-bind:properties="{
                                name: 'template_percent',
                                suffix: '%',
                                placeholder: '0.00',
                                label: 'Template %',
                              }"
                              v-bind:options="{
                                length: 4,
                                precision: 2,
                                empty: null,
                              }"
                              v-bind:label="'Template %'"
                            >
                            </v-text-field-money>
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
          <div style="width: 100%; overflow-x: scroll" v-if="permissions.project_list">
            <div style="width: 2000px">
              <v-data-table
                :headers="headers"
                :items="projects"
                :search="search"
                :loading="loading"
                :items-per-page="30"
                :footer-props="{
                  'items-per-page-options': [30, 40, 50, -1],
                }"
                loading-text="Loading... Please wait"
              >
                <template v-slot:item.template_percent="{ item }">
                  {{ item.template_percent ? item.template_percent + "%" : "" }}
                </template>

                <template v-slot:item.actions="{ item }">
                  <v-icon
                    small
                    class="mr-2"
                    color="green"
                    @click="editProject(item)"
                    v-if="permissions.project_edit"
                  >
                    mdi-pencil
                  </v-icon>
                  <v-icon
                    small
                    color="red"
                    @click="showConfirmAlert(item)"
                     v-if="permissions.project_delete"
                  >
                    mdi-delete
                  </v-icon>
                </template>
              </v-data-table>
            </div>
          </div>
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
    editedItem: {
      report_title: { required },
      department_id: { required },
      programmer_id: { required },
      type: { required },
    },
  },
  data() {
    return {
      absolute: true,
      overlay: false,
      search: "",
      headers: [
        { text: "Ref No.", value: "ref_no" },
        { text: "Report Title", value: "report_title" },
        { text: "Department", value: "department" },
        { text: "Programmer", value: "programmer" },
        { text: "Validator", value: "validator" },
        { text: "Date Logged", value: "date_logged" },
        { text: "Date Received", value: "date_received" },
        { text: "Date Approved", value: "date_approved" },
        { text: "User Type", value: "type" },
        { text: "Ideal Prog Hrs.", value: "ideal_prog_hrs" },
        { text: "Ideal Valid Hrs.", value: "ideal_valid_hrs" },
        { text: "Template %", value: "template_percent" },
        { text: "Actions", value: "actions", width: "80px", sortable: false },
      ],
      input_date_received: false,
      input_date_approved: false,
      date_received: "",
      date_approved: "",
      disabled: false,
      dialog: false,
      projects: [],
      departments: [],
      programmers: [],
      validators: [],
      types: [
        { text: "New", value: "New" },
        { text: "Change Order", value: "Change Order" },
      ],
      editedIndex: -1,
      editedItem: {
        ref_no: "",
        report_title: "",
        department: "",
        department_id: "",
        manager: "",
        programmer: "",
        programmer_id: "",
        validator: "",
        validator_id: "",
        date_received: "",
        date_approved: "",
        type: "",
        ideal_prog_hrs: "",
        ideal_valid_hrs: "",
        template_percent: "",
      },
      defaultItem: {
        ref_no: "",
        report_title: "",
        department: "",
        department_id: "",
        manager: "",
        programmer: "",
        programmer_id: "",
        validator: "",
        validator_id: "",
        date_received: "",
        date_approved: "",
        type: "",
        ideal_prog_hrs: "",
        ideal_valid_hrs: "",
        template_percent: "",
      },
      permissions: Home.data().permissions,
      loading: true,
      user: localStorage.getItem("user"),
      user_type: localStorage.getItem("user_type"),
      user_id: localStorage.getItem("user_id"),
    };
  },

  methods: {
    getProject() {
      this.loading = true;
      Axios.get("/api/project/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          this.projects = response.data.projects;
          this.departments = response.data.departments;
          this.programmers = response.data.programmers;
          this.validators = response.data.validators;
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

    editProject(item) {
      let date_received = "";
      let date_approved = "";

      this.editedIndex = this.projects.indexOf(item);
      this.editedItem = Object.assign({}, item);

      if (item.date_received) {
        date_received = item.date_received.split("/");
        this.date_received =
          date_received[2] + "-" + date_received[0] + "-" + date_received[1];
      }
      if (item.date_approved) {
        date_approved = item.date_approved.split("/");
        this.date_approved =
          date_approved[2] + "-" + date_approved[0] + "-" + date_approved[1];
      }

      this.dialog = true;
    },

    deleteProject(project_id) {
      const data = { project_id: project_id };

      Axios.post("/api/project/delete", data, {
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

          const project_id = item.project_id;
          const index = this.projects.indexOf(item);

          //Call delete Patient function
          this.deleteProject(project_id);

          //Remove item from array services
          this.projects.splice(index, 1);

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
          const project_id = this.editedItem.project_id;

          Axios.post("/api/project/update/" + project_id, data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              if (response.data.success) {
                Object.assign(this.projects[this.editedIndex], this.editedItem);
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

          Axios.post("/api/project/store", data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              if (response.data.success) {
                this.showAlert();
                this.close();

                //push recently added data from database
                this.projects.unshift(response.data.project);
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
      this.editedItem = this.defaultItem;
      this.date_received = "";
      this.date_approved = "";

      if (this.user_type == "Programmer") {
        this.editedItem.programmer_id = parseInt(this.user_id);
        this.editedItem.programmer = this.user;
      }
    },
    getRefNumber() {
      Axios.get("/api/project/get_ref_no", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          let ref_no = response.data;
          this.editedItem.ref_no = ref_no;
        },
        (error) => {
          console.log(error);
        }
      );
    },
    formatDate(date) {
      if (!date) return null;

      const [year, month, day] = date.split("-");
      return `${month}/${day}/${year}`;
    },
    departmentOnChange() {
      let department_id = this.editedItem.department_id;

      for (let [key, val] of this.departments.entries()) {
        if (department_id == val.id) {
          this.editedItem.department = val.name;
        }
      }
    },
    programmerOnChange() {
      let programmer_id = this.editedItem.programmer_id;

      for (let [key, val] of this.programmers.entries()) {
        if (programmer_id == val.id) {
          this.editedItem.programmer = val.name;
        }
      }
    },
    validatorOnChange() {
      let validator_id = this.editedItem.validator_id;

      for (let [key, val] of this.validators.entries()) {
        if (validator_id == val.id) {
          this.editedItem.validator = val.name;
        }
      }
    },
    importExcel() {},

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
      this.permissions.project_list = Home.methods.hasPermission([
        "project-list",
      ]);
      this.permissions.project_create = Home.methods.hasPermission([
        "project-create",
      ]);
      this.permissions.project_edit = Home.methods.hasPermission([
        "project-edit",
      ]);
      this.permissions.project_delete = Home.methods.hasPermission([
        "project-delete",
      ]);
      this.permissions.import_project = Home.methods.hasPermission([
        "import-project",
      ]);

      // hide column actions if user has no permission
      if (!this.permissions.project_edit && !this.permissions.project_delete) {
        this.headers[12].align = " d-none";
      }
      else
      {
        this.headers[12].align = "";
      }

      // if user is not authorize
      if (!this.permissions.project_list && !this.permissions.project_create) {
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

        if(action == 'project-create' || action == 'project-edit' || action == 'project-delete')
        {
          this.getProject();
        }

      });
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Project" : "Edit Project";
    },
    report_titleErrors() {
      const errors = [];
      if (!this.$v.editedItem.report_title.$dirty) return errors;
      !this.$v.editedItem.report_title.required &&
        errors.push("Report Title is required.");
      return errors;
    },
    departmentErrors() {
      const errors = [];
      if (!this.$v.editedItem.department_id.$dirty) return errors;
      !this.$v.editedItem.department_id.required &&
        errors.push("Department is required.");
      return errors;
    },
    programmerErrors() {
      const errors = [];
      if (!this.$v.editedItem.programmer_id.$dirty) return errors;
      !this.$v.editedItem.programmer_id.required &&
        errors.push("Programmer is required.");
      return errors;
    },
    computedDateReceivedFormatted() {
      this.editedItem.date_received = this.formatDate(this.date_received);
      return this.editedItem.date_received;
    },
    computedDateApprovedFormatted() {
      this.editedItem.date_approved = this.formatDate(this.date_approved);
      return this.editedItem.date_approved;
    },
    typeErrors() {
      const errors = [];
      if (!this.$v.editedItem.type.$dirty) return errors;
      !this.$v.editedItem.type.required && errors.push("Type is required.");
      return errors;
    },
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getProject();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>