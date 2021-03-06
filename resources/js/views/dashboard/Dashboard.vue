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
              v-if="userPermissions.import_project"
            ></v-divider>
            <v-btn
              color="primary"
              small
              class="ml-2"
              @click="importExcel()"
              v-if="userPermissions.import_project"
            >
              <v-icon small class="mr-1"> mdi-import </v-icon> Import
            </v-btn>

            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              v-if="userPermissions.project_list"
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
                  v-if="userPermissions.project_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>

                <v-dialog v-model="dialog" max-width="700px" persistent>
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
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
                              v-model="input_date_receive"
                              :close-on-content-click="false"
                              transition="scale-transition"
                              offset-y
                              max-width="290px"
                              min-width="290px"
                            >
                              <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                  name="date_receive"
                                  v-model="computedDateReceiveFormatted"
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
                                v-model="date_receive"
                                no-title
                                @input="input_date_receive = false"
                              ></v-date-picker>
                            </v-menu>
                          </v-col>
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-menu
                              v-model="input_date_approve"
                              :close-on-content-click="false"
                              transition="scale-transition"
                              offset-y
                              max-width="290px"
                              min-width="290px"
                            >
                              <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                  name="date_receive"
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
                                v-model="date_approve"
                                no-title
                                @input="input_date_approve = false"
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
                <v-dialog v-model="dialog_import" max-width="500px" persistent>
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
                      <span class="headline">Import Projects</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-file-input
                              v-model="file"
                              show-size
                              label="File input"
                              prepend-icon="mdi-paperclip"
                              required
                              :error-messages="fileErrors"
                              @change="$v.file.$touch() + (fileIsEmpty = false)"
                              @blur="$v.file.$touch()"
                            >
                              <template v-slot:selection="{ text }">
                                <v-chip small label color="primary">
                                  {{ text }}
                                </v-chip>
                              </template>
                            </v-file-input>
                          </v-col>
                        </v-row>
                        <v-row
                          class="fill-height"
                          align-content="center"
                          justify="center"
                          v-if="uploading"
                        >
                          <v-col class="subtitle-1 text-center" cols="12">
                            Uploading...
                          </v-col>
                          <v-col cols="6">
                            <v-progress-linear
                              color="primary"
                              indeterminate
                              rounded
                              height="6"
                            ></v-progress-linear>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>
                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn
                        color="#E0E0E0"
                        @click="dialog_import = false"
                        class="mb-4"
                      >
                        Cancel
                      </v-btn>
                      <v-btn
                        color="primary"
                        class="mb-4 mr-4"
                        @click="uploadFile()"
                        :disabled="uploadDisabled"
                      >
                        Upload
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
                <v-dialog
                  v-model="dialog_error_list"
                  max-width="1000px"
                  persistent
                >
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
                      <span class="headline">Error List</span>
                      <v-spacer></v-spacer>
                      <v-icon @click="dialog_error_list = false">
                        mdi-close
                      </v-icon>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col>
                            <v-simple-table dense>
                              <thead>
                                <tr>
                                  <th>#</th>
                                  <th>Error Message</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr
                                  v-for="(item, index) in imported_file_errors"
                                >
                                  <td>{{ index + 1 }}</td>
                                  <td v-html="item"></td>
                                </tr>
                              </tbody>
                            </v-simple-table>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>
                  </v-card>
                </v-dialog>
                <v-dialog
                  v-model="dialog_endorse_history"
                  max-width="1000px"
                  persistent
                >
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
                      <span class="headline">Endorse History</span>
                      <v-spacer></v-spacer>
                      <v-icon @click="dialog_endorse_history = false">
                        mdi-close
                      </v-icon>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col>
                            <v-data-table
                              :headers="endorse_history_headers"
                              :items="endorse_history"
                              :loading="loading_endorse_history"
                              loading-text="Loading... Please wait"
                            >
                            </v-data-table>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
          <div
            style="width: 100%; overflow-x: scroll"
            v-if="userPermissions.project_list"
          >
            <div style="width: 2200px">
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
                <template v-slot:item.status="{ item, index }">
                  <v-chip
                    :color="
                      item.status == 'For Validation'
                        ? 'info'
                        : item.status == 'Ongoing'
                        ? 'secondary'
                        : item.status == 'Pending'
                        ? 'warning'
                        : item.status == 'Accepted'
                        ? 'success'
                        : 'error'
                    "
                  >
                    {{ item.status }}
                  </v-chip>
                </template>
                <template v-slot:item.actions="{ item }">
                  <v-icon
                    small
                    class="mr-1"
                    color="info"
                    @click="viewEndorseHistory(item)"
                  >
                    mdi-eye
                  </v-icon>
                  <v-icon
                    small
                    class="mr-1"
                    color="green"
                    @click="editProject(item)"
                    v-if="userPermissions.project_edit"
                  >
                    mdi-pencil
                  </v-icon>
                  <v-icon
                    small
                    color="red"
                    @click="showConfirmAlert(item)"
                    v-if="userPermissions.project_delete"
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

import axios from "axios";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";
import { mapState } from 'vuex';

export default {

  mixins: [validationMixin],

  validations: {
    editedItem: {
      report_title: { required },
      department_id: { required },
      programmer_id: { required },
      type: { required },
    },
    file: { required },
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
        { text: "Date Received", value: "date_receive" },
        { text: "Date Approved", value: "date_approve" },
        { text: "Report Type", value: "type" },
        { text: "Ideal Prog Hrs.", value: "ideal_prog_hrs" },
        { text: "Ideal Valid Hrs.", value: "ideal_valid_hrs" },
        { text: "Template %", value: "template_percent" },
        { text: "Status", value: "status" },
        { text: "Actions", value: "actions", width: "100px", sortable: false },
      ],
      endorse_history_headers: [
        { text: "Endorse To", value: "programmer" },
        { text: "Endorsed Date", value: "endorse_date" },
        { text: "Date Received", value: "date_receive" },
        { text: "Program Date", value: "program_date" },
        { text: "Validation Date", value: "validation_date" },
      ],
      input_date_receive: false,
      input_date_approve: false,
      date_receive: "",
      date_approve: "",
      disabled: false,
      uploadDisabled: false,
      uploading: false,
      dialog: false,
      dialog_import: false,
      dialog_error_list: false,
      dialog_endorse_history: false,
      file: [],
      fileIsEmpty: false,
      fileIsInvalid: false,
      projects: [],
      departments: [],
      programmers: [],
      validators: [],
      errors_array: [],
      endorse_history: [],
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
        date_receive: "",
        date_approve: "",
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
        date_receive: "",
        date_approve: "",
        type: "",
        ideal_prog_hrs: "",
        ideal_valid_hrs: "",
        template_percent: "",
      },
      loading: true,
      loading_endorse_history: true,

    };
  },

  methods: {
    
    getProject() {
      this.loading = true;
      axios.get("/api/project/index").then(
        (response) => {
          this.projects = response.data.projects;
          this.departments = response.data.departments;
          this.programmers = response.data.programmers;
          this.validators = response.data.validators;
          this.loading = false;

          // console.log(this.projects);
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editProject(item) {
      let date_receive = "";
      let date_approve = "";

      this.editedIndex = this.projects.indexOf(item);
      this.editedItem = Object.assign({}, item);

      if (item.date_receive) {
        date_receive = item.date_receive.split("/");
        this.date_receive =
          date_receive[2] + "-" + date_receive[0] + "-" + date_receive[1];
      }
      if (item.date_approve) {
        date_approve = item.date_approve.split("/");
        this.date_approve =
          date_approve[2] + "-" + date_approve[0] + "-" + date_approve[1];
      }

      this.dialog = true;
    },

    deleteProject(project_id) {
      const data = { project_id: project_id };

      axios.post("/api/project/delete", data).then(
        (response) => {
          // console.log(response.data);
          if (response.data.success) {
            // send data to Socket.IO Server
            this.$socket.emit("sendData", { action: "project-delete" });
          }
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    viewEndorseHistory(item) {
      this.dialog_endorse_history = true;
      this.loading_endorse_history = true;
      this.endorse_history = [];
      axios
        .post("/api/project/endorse_history", {
          project_id: item.project_id,
        })
        .then(
          (response) => {
            if (response.data.endorse_history) {
              this.endorse_history = response.data.endorse_history;
              this.loading_endorse_history = false;
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

          const project_id = item.project_id;
          const index = this.projects.indexOf(item);

          //Call delete Project function
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

      if (!this.$v.editedItem.$error) {
        this.overlay = true;
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedItem;
          const project_id = this.editedItem.project_id;

          axios.post("/api/project/update/" + project_id, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "project-edit" });

                Object.assign(this.projects[this.editedIndex], this.editedItem);
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

          axios.post("/api/project/store", data).then(
            (response) => {
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "project-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.projects.unshift(response.data.project);
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
      this.editedItem = this.defaultItem;
      this.date_receive = "";
      this.date_approve = "";

      if (this.user_type == "Programmer") {
        this.editedItem.programmer_id = parseInt(this.user_id);
        this.editedItem.programmer = this.user;
      }
    },
    getRefNumber() {
      axios.get("/api/project/get_ref_no").then(
        (response) => {
          let ref_no = response.data;
          this.editedItem.ref_no = ref_no;
        },
        (error) => {
          this.isUnauthorized(error);
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

    importExcel() {
      this.dialog_import = true;
      this.file = [];
      this.$v.$reset();
    },

    uploadFile() {
      this.$v.$touch();
      this.fileIsEmpty = false;
      this.fileIsInvalid = false;

      if (!this.$v.file.$error) {
        this.uploadDisabled = true;
        this.uploading = true;
        let formData = new FormData();

        formData.append("file", this.file);
        // console.log(this.file);
        axios.post("api/project/import_project", formData).then(
          (response) => {
            this.errors_array = [];

            if (response.data.success) {
              // send data to Socket.IO Server
              this.$socket.emit("sendData", { action: "import-project" });

              this.$swal({
                position: "center",
                icon: "success",
                title: "Record has been imported",
                showConfirmButton: false,
                timer: 2500,
              });
              this.$v.$reset();
              this.dialog_import = false;
              this.file = [];
              this.fileIsEmpty = false;
            } else if (response.data.error_column_name) {
              this.errors_array = response.data.error_column_name;
              this.dialog_error_list = true;
            } else if (response.data.error_row_data) {
              let error_keys = Object.keys(response.data.error_row_data);
              let errors = response.data.error_row_data;
              let field_values = response.data.field_values;
              let row = "";
              let col = "";

              error_keys.forEach((value, index) => {
                row = value.split(".")[0];
                col = value.split(".")[1];
                errors[value].forEach((val, i) => {
                  this.errors_array.push(
                    "Error on Index: <label class='text-info'>" +
                      row +
                      "</label>; Column: <label class='text-primary'>" +
                      col +
                      "</label>; Msg: <label class='text-danger'>" +
                      val +
                      "</label>; Value: <label class='text-success'>" +
                      field_values[row][col] +
                      "</label>"
                  );
                });
              });

              this.dialog_error_list = true;
            } else if (response.data.error_empty) {
              this.fileIsEmpty = true;
            } else {
              this.fileIsInvalid = true;
            }

            this.uploadDisabled = false;
            this.uploading = false;
          },
          (error) => {
            this.isUnauthorized(error);
            this.uploadDisabled = false;
          }
        );
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
        
        if (
          action == "project-create" ||
          action == "project-edit" ||
          action == "project-delete" ||
          action == "import-project"
        ) {
          this.getProject();
        }
      };
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
    computedDateReceiveFormatted() {
      this.editedItem.date_receive = this.formatDate(this.date_receive);
      return this.editedItem.date_receive;
    },
    computedDateApprovedFormatted() {
      this.editedItem.date_approve = this.formatDate(this.date_approve);
      return this.editedItem.date_approve;
    },
    typeErrors() {
      const errors = [];
      if (!this.$v.editedItem.type.$dirty) return errors;
      !this.$v.editedItem.type.required && errors.push("Type is required.");
      return errors;
    },
    fileErrors() {
      const errors = [];
      if (!this.$v.file.$dirty) return errors;
      !this.$v.file.required && errors.push("File is required.");
      this.fileIsEmpty && errors.push("File is empty.");
      this.fileIsInvalid &&
        errors.push("File type must be 'xlsx', 'xls' or 'ods'.");
      return errors;
    },
    imported_file_errors() {
      return this.errors_array.sort();
    },
    ...mapState('auth', { user: 'user_name', user_id: 'user_id', user_type: 'user_type' }),
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getProject();
    this.websocket();
  },
};
</script>