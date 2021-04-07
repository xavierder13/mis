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
            <v-spacer></v-spacer>

            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="
                    clear() +
                      (dialog = true) +
                      (editedItem.status = project.status) +
                      setStatusSelectItems()
                  "
                  v-if="project.status != 'Accepted'"
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
import Home from '../Home.vue';  

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
    Home
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
      project_logs: [],
      project: {},
      types: [
        { text: "New", value: "New" },
        { text: "Change Order", value: "Change Order" },
      ],
      editedIndex: -1,
      editedItem: {
        status: "",
        remarks_date: new Date().toISOString().substr(0, 10),
        remarks_time: "",
        hour: String(new Date().toTimeString().substr(0, 5).split(':')[0]),
        minute: String(new Date().toTimeString().substr(0, 5).split(':')[1]),
        remarks: "",
        turnover: "",
      },
      defaultItem: {
        status: "",
        remarks_date: new Date().toISOString().substr(0, 10),
        remarks_time: "",
        hour: String(new Date().toTimeString().substr(0, 5).split(':')[0]),
        minute: String(new Date().toTimeString().substr(0, 5).split(':')[1]),
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

      Axios.get("/api/project_log/index/" + project_id, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          console.log(response.data);
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
      this.$v.$touch();
      if (!this.$v.$error) {
        this.overlay = true;
        this.disabled = true;
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
              console.log(response.data);
              if (response.data.success) {
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

          Axios.post("/api/project_log/store", this.editedItem, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              console.log(response.data);
              if (response.data.success) {
                this.project_logs.push(response.data.project_log);

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
      this.setStatusSelectItems();
      let remarks_date = "";
      this.dialog = true;
      this.editedIndex = this.project_logs.indexOf(item);
      this.editedItem = Object.assign({}, item);
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
          console.log(response.data);
          this.getProjectLogs();
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
          this.report_status = [
            { text: "Ongoing", value: "Ongoing" },
            { text: "Pending", value: "Pending" },
            { text: "Accepted", value: "Accepted" },
            { text: "Cancelled", value: "Cancelled" },
          ];
        } else if (this.project.status == "For Validation") {
          this.report_status = [
            { text: "For Validation", value: "For Validation" },
            { text: "Pending", value: "Pending" },
            { text: "Accepted", value: "Accepted" },
            { text: "Cancelled", value: "Cancelled" },
          ];
        } else if (this.project.status == "Pending") {
          if (last_log_status == "Ongoing") {
            this.report_status = [
              { text: "For Validation", value: "For Validation" },
              { text: "Pending", value: "Pending" },
              { text: "Accepted", value: "Accepted" },
              { text: "Cancelled", value: "Cancelled" },
            ];
          } else if (last_log_status == "For Validation") {
            this.report_status = [
              { text: "Ongoing", value: "Ongoing" },
              { text: "Pending", value: "Pending" },
              { text: "Accepted", value: "Accepted" },
              { text: "Cancelled", value: "Cancelled" },
            ];
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

      // hide column actions if user has no permission
      if (!this.permissions.project_log_edit && !this.permissions.project_log_delete) {
        this.headers[7].align = " d-none";
      }
      else
      {
        this.headers[7].align = "";
      }

      // if user is not authorize
      if (!this.permissions.project_log_list && !this.permissions.project_log_create) {
        this.$router.push("/unauthorize").catch(() => {});
      }
      
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
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getProjectLogs();
    this.userRolesPermissions();

    for (let hour = 1; hour <= 24; hour++) {
      let hr = hour < 10 ? '0'+hour : hour;
      this.hour.push(String(hr));
    }

    for (let minute = 1; minute <= 60; minute++) {
      let min = minute < 10 ? '0'+minute : minute;
      this.minute.push(String(min));
    }


  },
};
</script>