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
            {{
              project.ref_no
                ? project.ref_no + " - " + project.report_title
                : ""
            }}

            <v-chip
              v-if="project.status"
              :color="
                project.status == 'For Validation'
                  ? 'info'
                  : project.status == 'Ongoing'
                  ? 'secondary'
                  : project.status == 'Pending'
                  ? 'warning'
                  : project.status == 'Accepted'
                  ? 'success'
                  : 'error'
              "
              class="ml-4"
              outlined
            >
              {{ project.status }}
            </v-chip>

            <!-- <v-divider
              vertical
              class="ml-2"
              v-if="permissions.export_project_log"
            ></v-divider>
            <export-excel
              class="btn btn-default"
              :data="filteredProjects"
              :fields="json_fields"
              worksheet="My Worksheet"
              name="filename.xls"
              v-if="permissions.export_project_log"
            >
              <v-icon
                :disabled="printDisabled"
                color="success"
                v-if="permissions.export_project_log"
              >
                mdi-file-excel
              </v-icon>
            </export-excel> -->
            <v-divider
              vertical
              class="ml-3"
              v-if="permissions.import_project_log"
            ></v-divider>
            <v-icon
              color="primary"
              class="ml-2"
              @click="importExcel()"
              v-if="permissions.import_project_log"
            >
              mdi-import
            </v-icon>
            <v-spacer></v-spacer>

            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="showProjectLogDialog()"
                  v-if="project.status != 'Accepted'"
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
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-select
                              v-model="editedItem.status"
                              label="Report Status"
                              :items="report_status"
                              item-text="text"
                              item-value="value"
                              :readonly="editedIndex > -1 ? true : false"
                              @change="statusOnChange()"
                            ></v-select>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="6">
                            <v-menu
                              v-model="input_remarks_date"
                              :close-on-content-click="false"
                              transition="scale-transition"
                              offset-y
                              max-width="290px"
                              min-width="290px"
                            >
                              <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                  name="remarks_date"
                                  v-model="computedRemarksDateFormatted"
                                  label="Date"
                                  hint="MM/DD/YYYY"
                                  persistent-hint
                                  prepend-icon="mdi-calendar"
                                  readonly
                                  v-bind="attrs"
                                  v-on="on"
                                  :error-messages="remarks_dateErrors"
                                  @input="$v.editedItem.remarks_date.$touch()"
                                  @blur="$v.editedItem.remarks_date.$touch()"
                                ></v-text-field>
                              </template>
                              <v-date-picker
                                v-model="editedItem.remarks_date"
                                no-title
                                @input="input_remarks_date = false"
                              ></v-date-picker>
                            </v-menu>
                          </v-col>
                          <v-col cols="2">
                            <!-- <v-dialog
                              ref="dialog"
                              v-model="time_modal"
                              :return-value.sync="editedItem.remarks_time"
                              persistent
                              width="290px"
                              persistent
                            >
                              <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                  v-model="editedItem.remarks_time"
                                  label="Time"
                                  prepend-icon="mdi-clock-time-four-outline"
                                  scrollable
                                  readonly
                                  v-bind="attrs"
                                  v-on="on"
                                  :error-messages="remarks_timeErrors"
                                  @input="$v.editedItem.remarks_time.$touch()"
                                  @blur="$v.editedItem.remarks_time.$touch()"
                                ></v-text-field>
                              </template>
                              <v-time-picker
                                v-if="time_modal"
                                v-model="editedItem.remarks_time"
                                full-width
                                scrollable
                              >
                                <v-spacer></v-spacer>
                                <v-btn
                                  text
                                  color="primary"
                                  @click="time_modal = false"
                                >
                                  Cancel
                                </v-btn>
                                <v-btn
                                  text
                                  color="primary"
                                  @click="
                                    $refs.dialog.save(editedItem.remarks_time)
                                  "
                                >
                                  OK
                                </v-btn>
                              </v-time-picker>
                            </v-dialog> -->
                            <!-- <v-text-field-simplemask
                              v-model="editedItem.remarks_time"
                              label="Time"
                              v-bind:properties="{
                                prefix: '',
                                suffix: '',
                                readonly: false,
                                disabled: false,
                                outlined: false,
                                clearable: true,
                                placeholder: '',
                                keyup: validateTime(),
                                error: timeHasError,
                                messages: remarks_timeErrors,
                              }"
                              v-bind:options="{
                                inputMask: '##:##',
                                outputMask: '##:##',
                                empty: null,
                                applyAfter: false,
                                alphanumeric: false,
                                lowerCase: false,
                              }"
                              v-bind:focus="focus"
                              v-on:focus="focus = false"
                              @blur="$v.editedItem.remarks_time.$touch()"
                            /> -->
                            <v-autocomplete
                              v-model="editedItem.hour"
                              :items="hour"
                              label="Hour"
                              @blur="$v.editedItem.hour.$touch()"
                              :hint="'24 Hr Format'"
                              persistent-hint
                            ></v-autocomplete>
                          </v-col>
                          <v-col cols="2">
                            <v-autocomplete
                              v-model="editedItem.minute"
                              :items="minute"
                              label="Minute"
                              @blur="$v.editedItem.minute.$touch()"
                            ></v-autocomplete>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-textarea
                              v-model="editedItem.remarks"
                              label="Remarks"
                              rows="2"
                              :error-messages="remarksErrors"
                              @input="$v.editedItem.remarks.$touch()"
                              @blur="$v.editedItem.remarks.$touch()"
                            ></v-textarea>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="4">
                            <v-switch
                              v-model="switch1"
                              label="Turn Over"
                              @click="turnoverStatus()"
                              :disabled="disabledSwitch"
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
                        class="mb-4 mr-4"
                        :disabled="disabled"
                        @click="save()"
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
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
                  @click="(dialog_import = false) + (uploading = false)"
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
          <v-dialog v-model="dialog_error_list" max-width="1000px" persistent>
            <v-card>
              <v-card-title class="mb-0 pb-0">
                <span class="headline">Error List</span>
                <v-spacer></v-spacer>
                <v-icon @click="dialog_error_list = false"> mdi-close </v-icon>
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
                          <tr v-for="(item, index) in imported_file_errors">
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
          <v-data-table
            :headers="headers"
            :items="project_logs"
            :search="search"
            :loading="loading"
            :items-per-page="30"
            :footer-props="{
              'items-per-page-options': [30, 40, 50, -1],
            }"
            loading-text="Loading... Please wait"
          >
            <template v-slot:item.index="{ item, index }">
              {{ index + 1 }}
            </template>
            <template v-slot:item.status="{ item, index }">
              <v-chip
                v-if="editedIndex != index"
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

            <template v-slot:item.turnover="{ item, index }">
              <v-chip
                class="cyan darken-1 white--text"
                v-if="editedIndex != index && item.turnover == 'Y'"
              >
                Turn Over
              </v-chip>
            </template>
            <template
              v-slot:item.actions="{ item, index }"
              v-if="project.status != 'Accepted'"
            >
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editProjectLog(item)"
              >
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
import moment from "moment";
import Home from "../Home.vue";

let now_date = moment(new Date().toISOString().substring(0, 10), "YYYY-MM-DD");
let now_datetime = moment(new Date(), "YYYY-MM-DD");
let now_datetime_start = moment(
  new Date(new Date().toISOString().substring(0, 10) + " 08:00:00"),
  "YYYY-MM-DD"
);
let now_datetime_end = moment(new Date(now_date + " 17:00:00"), "YYYY-MM-DD");
let now_noon_time = new Date(
  new Date().toISOString().substring(0, 10) + " 12:00:00"
);

export default {
  components: {
    Home,
  },

  mixins: [validationMixin],

  validations: {
    editedItem: {
      status: { required },
      remarks_date: { required },
      // remarks_time: { required },
      hour: { required },
      minute: { required },
      remarks: { required },
    },
    file: { required },
  },
  data() {
    return {
      hour: [],
      minute: [],
      focus: false,
      disabledSwitch: false,
      switch1: false,
      absolute: true,
      overlay: false,
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/dashboard",
        },
        {
          text: "Programmer's Projects",
          disabled: false,
          link: "/programmer_reports",
        },
        {
          text: "Project",
          disabled: true,
        },
      ],
      search: "",
      headers: [
        { text: "#", value: "index", sortable: false },
        { text: "Date", value: "remarks_date", sortable: false },
        { text: "Time", value: "remarks_time", sortable: false },
        { text: "Status", value: "status", sortable: false },
        { text: "Remarks", value: "remarks", sortable: false },
        { text: "Turn Over", value: "turnover", sortable: false },
        { text: "Minutes Diff", value: "mins_diff", sortable: false },
        { text: "Actions", value: "actions", sortable: false, width: "80px" },
      ],
      disabled: false,
      dialog: false,
      dialog_import: false,
      dialog_error_list: false,
      uploadDisabled: false,
      fileIsEmpty: false,
      fileIsInvalid: false,
      uploading: false,
      project_logs: [],
      project: {},
      file: [],
      errors_array: [],
      types: [
        { text: "New", value: "New" },
        { text: "Change Order", value: "Change Order" },
      ],
      editedIndex: -1,
      editedItem: {
        project_id: "",
        endorse_project_id: "",
        status: "",
        remarks_date: new Date().toISOString().substr(0, 10),
        remarks_time: "",
        hour: String(new Date().toTimeString().substr(0, 5).split(":")[0]),
        minute: String(new Date().toTimeString().substr(0, 5).split(":")[1]),
        remarks: "",
        turnover: "",
      },
      defaultItem: {
        project_id: "",
        endorse_project_id: "",
        status: "",
        remarks_date: new Date().toISOString().substr(0, 10),
        remarks_time: "",
        hour: String(new Date().toTimeString().substr(0, 5).split(":")[0]),
        minute: String(new Date().toTimeString().substr(0, 5).split(":")[1]),
        remarks: "",
        turnover: "",
      },
      permissions: Home.data().permissions,
      loading: true,
      report_status: [],
      status: [],
      input_remarks_date: false,
      time_modal: false,
      error_status: null,
      timeHasError: false,
    };
  },

  methods: {
    getProjectLogs() {
      this.loading = true;

      let project_id = this.$route.params.project_id;
      const data = {
        endorse_project_id: this.$route.params.endorse_project_id,
      };

      Axios.post("/api/project_log/index/" + project_id, data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          // console.log(response.data);
          this.project = response.data.project;
          this.project_logs = response.data.project_logs;
          this.loading = false;
          this.computeProgramHours;
          this.computeValidateHours;

          // this.setStatusSelectItems();
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
    save() {
      this.$v.editedItem.$touch();

      if (!this.$v.editedItem.$error) {
        this.overlay = true;
        this.disabled = true;

        this.editedItem.remarks_time =
          this.editedItem.hour + ":" + this.editedItem.minute;

        if (this.editedIndex > -1) {
          let project_log_id = this.editedItem.id;

          Axios.post(
            "/api/project_log/update/" + project_log_id,
            this.editedItem,
            {
              headers: {
                Authorization: "Bearer " + access_token,
              },
            }
          ).then(
            (response) => {
              if (response.data.success) {
                // if hasChanges is true - changes in status, program date or validation date
                if (response.data.hasChanges) {
                  // send data to Socket.IO Server
                  this.$socket.emit("sendData", { action: "project-edit" });
                }

                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "project-log-edit" });

                Object.assign(
                  this.project_logs[this.editedIndex],
                  response.data.project_log
                );

                this.project.status = response.data.status;

                this.showAlert();
                this.close();
                this.getProjectLogs();
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
          this.editedItem.project_id = this.project.project_id;
          this.editedItem.endorse_project_id = this.$route.params.endorse_project_id;
          Axios.post("/api/project_log/store", this.editedItem, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "project-log-create" });

                this.project_logs.push(response.data.project_log);

                // if hasChanges is true - changes in status, program date or validation date
                if (response.data.hasChanges) {
                  // send data to Socket.IO Server
                  this.$socket.emit("sendData", { action: "project-edit" });
                }

                // assign new status from backend
                this.project.status = response.data.status;

                this.showAlert();
                this.close();
                this.getProjectLogs();
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

    editProjectLog(item) {
      // this.setStatusSelectItems();
      this.report_status = [item.status];
      let remarks_date = "";
      let hour = item.remarks_time.split(":")[0];
      let minute = item.remarks_time.split(":")[1];

      this.dialog = true;
      this.editedIndex = this.project_logs.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.editedItem.hour = String(hour);
      this.editedItem.minute = String(minute);

      if (item.remarks_date) {
        remarks_date = item.remarks_date.split("/");
        this.editedItem.remarks_date =
          remarks_date[2] + "-" + remarks_date[0] + "-" + remarks_date[1];
      }
      if (item.turnover == "Y") {
        this.switch1 = true;
      } else {
        this.switch1 = false;
      }

      if (item.status == "Ongoing" || item.status == "For Validation") {
        this.disabledSwitch = false;
      } else {
        this.disabledSwitch = true;
      }
    },

    deleteProjectLog(project_log_id) {
      const data = { project_log_id: project_log_id };

      Axios.post("/api/project_log/delete", data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          if (response.data.success) {
            // send data to Socket.IO Server
            this.$socket.emit("sendData", { action: "project-log-delete" });

            // if hasChanges is true - changes in status, program date or validation date
            if (response.data.hasChanges) {
              // send data to Socket.IO Server
              this.$socket.emit("sendData", { action: "project-edit" });
            }
          }
        },
        (error) => {
          console.log(error);
        }
      );
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

          const project_log_id = item.id;
          const index = this.project_logs.indexOf(item);

          //Call delete Patient function
          this.deleteProjectLog(project_log_id);

          //Remove item from array services
          this.project_logs.splice(index, 1);

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

    showAlert() {
      this.$swal({
        position: "center",
        icon: "success",
        title: "Record has been saved",
        showConfirmButton: false,
        timer: 2500,
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

    clear() {
      this.$v.$reset();
      this.editedIndex = -1;
      this.setStatusSelectItems();
      this.switch1 = false;
    },

    formatDate(date) {
      if (!date) return null;

      const [year, month, day] = date.split("-");
      return `${month}/${day}/${year}`;
    },
    turnoverStatus() {
      if (this.switch1) {
        this.editedItem.turnover = "Y";
      } else {
        this.editedItem.turnover = "";
      }
    },
    redirectToLogin() {
      // if unauthenticated (401)
      if (this.error_statusr) {
        localStorage.removeItem("access_token");
        this.$router.push({ name: "login" });
      }
    },
    setStatusSelectItems() {
      let hasOngoingTurnover = false;
      let logs_num_rows = this.project_logs.length;
      let last_log_status = "";

      this.report_status = [
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
        { text: "Cancelled", value: "Cancelled" },
      ];

      if (logs_num_rows == 0) {
        this.editedItem.status = "Ongoing";
      }

      // if logs has Ongoing and Turnover status
      this.project_logs.forEach((value, index) => {
        if (value.status == "Ongoing" && value.turnover == "Y") {
          hasOngoingTurnover = true;
        }

        // get the last index of status except "Pending"
        if (
          (value.status == "Ongoing" && value.turnover) ||
          (value.status == "For Validation" && value.turnover)
        ) {
          last_log_status = value.status;
        }
      });

      if (hasOngoingTurnover) {
        if (this.project.status == "Ongoing") {
          // remove index 0 (For Validation)
          this.report_status.splice(0, 1);
        } else if (this.project.status == "For Validation") {
          // remove index 1 (Ongoing)
          this.report_status.splice(1, 1);
        } else if (this.project.status == "Pending") {
          if (last_log_status == "Ongoing") {
            // remove index 1 (Ongoing)
            this.report_status.splice(1, 1);
          } else if (last_log_status == "For Validation") {
            // remove index 0 (For Validation)
            this.report_status.splice(0, 1);
          }
        }
      } else {
        this.report_status = [
          { text: "Ongoing", value: "Ongoing" },
          { text: "Pending", value: "Pending" },
          { text: "Cancelled", value: "Cancelled" },
        ];
      }
    },
    statusOnChange() {
      this.switch1 = false;

      let status = this.editedItem.status;

      if (status == "Ongoing" || status == "For Validation") {
        this.disabledSwitch = false;
      } else {
        this.disabledSwitch = true;
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
        let project_id = this.$route.params.project_id;

        formData.append("file", this.file);
        formData.append("project_id", project_id);

        Axios.post("api/project_log/import_project_log", formData, {
          headers: {
            Authorization: "Bearer " + access_token,
            "Content-Type": "multipart/form-data",
          },
        }).then(
          (response) => {
            console.log(response.data);
            this.errors_array = [];
            if (response.data.success) {
              // send data to Socket.IO Server
              this.$socket.emit("sendData", { action: "import-project-log" });

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
            console.log(error);
            this.uploadDisabled = false;
          }
        );
      }
    },
    showProjectLogDialog() {
      this.clear();
      this.dialog = true;
      this.editedItem.status = this.project.status;
      this.editedItem.hour = String(
        new Date().toTimeString().substr(0, 5).split(":")[0]
      );
      this.editedItem.minute = String(
        new Date().toTimeString().substr(0, 5).split(":")[1]
      );
    },

    setDropdownTime() {
      for (let hour = 0; hour < 24; hour++) {
        let hr = hour < 10 ? "0" + hour : hour;
        this.hour.push(String(hr));
      }

      for (let minute = 0; minute < 60; minute++) {
        let min = minute < 10 ? "0" + minute : minute;
        this.minute.push(String(min));
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
      this.permissions.project_log_list = Home.methods.hasPermission([
        "project-log-list",
      ]);
      this.permissions.project_log_create = Home.methods.hasPermission([
        "project-log-create",
      ]);
      this.permissions.project_log_edit = Home.methods.hasPermission([
        "project-log-edit",
      ]);
      this.permissions.project_log_delete = Home.methods.hasPermission([
        "project-log-delete",
      ]);
      this.permissions.import_project_log = Home.methods.hasPermission([
        "import-project-log",
      ]);
      this.permissions.export_project_log = Home.methods.hasPermission([
        "export-project-log",
      ]);

      // hide column actions if user has no permission
      if (
        !this.permissions.project_log_edit &&
        !this.permissions.project_log_delete
      ) {
        this.headers[7].align = " d-none";
      } else {
        this.headers[7].align = "";
      }

      // if user is not authorize
      if (
        !this.permissions.project_log_list &&
        !this.permissions.project_log_create
      ) {
        this.$router.push("/unauthorize").catch(() => {});
      }
    },
    websocket() {
      // window.Echo.channel("WebsocketChannel").listen("WebsocketEvent", (e) => {
      //   console.log(e);
      //   let action = e.data.action;
      //   if (
      //     action == "user-edit" ||
      //     action == "role-edit" ||
      //     action == "role-delete" ||
      //     action == "permission-delete"
      //   ) {
      //     this.userRolesPermissions();
      //   }

      //   if (
      //     action == "project-log-create" ||
      //     action == "project-log-edit" ||
      //     action == "project-log-delete" ||
      //     action == "import-project-log"
      //   ) {
      //     this.getProjectLogs();
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
          action == "project-log-create" ||
          action == "project-log-edit" ||
          action == "project-log-delete" ||
          action == "import-project-log"
        ) {
          this.getProjectLogs();
        }
      };
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Project Log" : "Edit Project Log";
    },
    computedRemarksDateFormatted() {
      return this.formatDate(this.editedItem.remarks_date);
    },
    remarks_dateErrors() {
      const errors = [];
      if (!this.$v.editedItem.remarks_date.$dirty) return errors;
      !this.$v.editedItem.remarks_date.required &&
        errors.push("Remarks Date is required.");
      return errors;
    },
    remarks_timeErrors() {
      const errors = [];
      if (!this.$v.editedItem.remarks_time.$dirty) return errors;
      !this.$v.editedItem.remarks_time.required && errors.push("Required.");

      if (this.timeHasError) {
        errors.push("Invalid");
      }

      return errors;
    },
    hourErrors() {
      const errors = [];
      if (!this.$v.editedItem.hour.$dirty) return errors;
      !this.$v.editedItem.hour.required && errors.push("Required.");

      if (this.timeHasError) {
        errors.push("Invalid");
      }

      return errors;
    },
    minuteErrors() {
      const errors = [];
      if (!this.$v.editedItem.minute.$dirty) return errors;
      !this.$v.editedItem.minute.required && errors.push("Required.");

      if (this.timeHasError) {
        errors.push("Invalid");
      }

      return errors;
    },
    remarksErrors() {
      const errors = [];
      if (!this.$v.editedItem.remarks.$dirty) return errors;
      !this.$v.editedItem.remarks.required &&
        errors.push("Remarks is required.");
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
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getProjectLogs();
    this.setDropdownTime();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>