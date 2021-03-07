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
            <v-select
              v-model="filter_project_by_programmer"
              :items="programmers"
              item-text="name"
              item-value="id"
              label="Programmer"
              hide-details=""
              v-if="user_type == 'Admin'"
            ></v-select>

            {{ user_type == "Programmer" ? user + " Projects" : "" }}

            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
            ></v-text-field>
            <v-spacer></v-spacer>
            <v-select
              v-model="search_report_status"
              :items="searchReportStatus"
              item-text="text"
              item-value="value"
              label="Report Status"
              hide-details=""
            ></v-select>
            <template>
              <v-toolbar flat>
                <v-dialog v-model="dialog" max-width="700px">
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>

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
                              readonly
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <!-- <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-datetime-picker
                              label="Select Datetime"
                              v-model="date_time"
                              dateFormat="MM/dd/yyyy"
                              timeFormat="HH:mm"
                            >
                            </v-datetime-picker>
                          </v-col> -->
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
                                  @input="$v.remarks_date.$touch()"
                                  @blur="$v.remarks_date.$touch()"
                                ></v-text-field>
                              </template>
                              <v-date-picker
                                v-model="remarks_date"
                                no-title
                                @input="input_remarks_date = false"
                              ></v-date-picker>
                            </v-menu>
                          </v-col>
                          <v-col cols="6">
                            <v-dialog
                              ref="dialog"
                              v-model="time_modal"
                              :return-value.sync="remarks_time"
                              persistent
                              width="290px"
                            >
                              <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                  v-model="remarks_time"
                                  label="Time"
                                  prepend-icon="mdi-clock-time-four-outline"
                                  scrollable
                                  readonly
                                  v-bind="attrs"
                                  v-on="on"
                                  :error-messages="remarks_timeErrors"
                                  @input="$v.remarks_time.$touch()"
                                  @blur="$v.remarks_time.$touch()"
                                ></v-text-field>
                              </template>
                              <v-time-picker
                                v-if="time_modal"
                                v-model="remarks_time"
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
                                  @click="$refs.dialog.save(remarks_time)"
                                >
                                  OK
                                </v-btn>
                              </v-time-picker>
                            </v-dialog>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-textarea
                              v-model="remarks"
                              label="Remarks"
                              rows="2"
                              :error-messages="remarksErrors"
                              @input="$v.remarks.$touch()"
                              @blur="$v.remarks.$touch()"
                            ></v-textarea>
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
                        @click="addRemarks()"
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
          <div style="width: 100%; overflow-x: scroll">
            <div style="width: 2700px">
              <v-data-table
                :headers="headers"
                :items="filteredProjects"
                :search="search"
                :loading="loading"
                :items-per-page="30"
                :footer-props="{
                  'items-per-page-options': [30, 40, 50, -1],
                }"
                loading-text="Loading... Please wait"
              >
                <template v-slot:item.template_percent="{ item, index }">
                  <v-text-field-money
                    v-model="editedItem.template_percent"
                    v-bind:properties="{
                      name: 'template_percent',
                      suffix: '%',
                      placeholder: '0.00',
                    }"
                    v-bind:options="{
                      length: 4,
                      precision: 2,
                      empty: null,
                    }"
                    v-if="editedIndex == index"
                  >
                  </v-text-field-money>

                  {{
                    item.template_percent && editedIndex != index
                      ? item.template_percent + "%"
                      : ""
                  }}
                </template>
                <template v-slot:item.program_date="{ item, index }">
                  <v-menu
                    v-model="input_program_date"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    max-width="290px"
                    min-width="290px"
                    v-if="editedIndex == index"
                  >
                    <template v-slot:activator="{ on, attrs }">
                      <v-text-field
                        name="program_date"
                        v-model="computedProgramDateFormatted"
                        hint="MM/DD/YYYY"
                        persistent-hint
                        prepend-icon="mdi-calendar"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="program_date"
                      no-title
                      @input="input_program_date = false"
                    ></v-date-picker>
                  </v-menu>
                  {{ editedIndex != index ? item.program_date : "" }}
                </template>
                <template v-slot:item.program_percent="{ item, index }">
                  <v-text-field-money
                    v-model="editedItem.program_percent"
                    v-bind:properties="{
                      name: 'program_percent',
                      suffix: '%',
                      placeholder: '0.00',
                    }"
                    v-bind:options="{
                      length: 4,
                      precision: 2,
                      empty: null,
                    }"
                    v-if="editedIndex == index"
                  >
                  </v-text-field-money>
                  {{
                    item.program_percent && editedIndex != index
                      ? item.program_percent + "%"
                      : ""
                  }}
                </template>
                <template v-slot:item.validation_date="{ item, index }">
                  <v-menu
                    v-model="input_validation_date"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    max-width="290px"
                    min-width="290px"
                    v-if="editedIndex == index"
                  >
                    <template v-slot:activator="{ on, attrs }">
                      <v-text-field
                        name="validation_date"
                        v-model="computedValidationDateFormatted"
                        hint="MM/DD/YYYY"
                        persistent-hint
                        prepend-icon="mdi-calendar"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="validation_date"
                      no-title
                      @input="input_validation_date = false"
                    ></v-date-picker>
                  </v-menu>
                  {{
                    item.validation_date && editedIndex != index
                      ? item.validation_date
                      : ""
                  }}
                </template>
                <template v-slot:item.validation_percent="{ item, index }">
                  <v-text-field-money
                    v-model="editedItem.validation_percent"
                    v-bind:properties="{
                      name: 'validation_percent',
                      suffix: '%',
                      placeholder: '0.00',
                    }"
                    v-bind:options="{
                      length: 4,
                      precision: 2,
                      empty: null,
                    }"
                    v-if="editedIndex == index"
                  >
                  </v-text-field-money>
                  {{
                    item.validation_percent && editedIndex != index
                      ? item.validation_percent + "%"
                      : ""
                  }}
                </template>
                <template v-slot:item.status="{ item, index }">
                  <v-select
                    v-model="editedItem.status"
                    :items="report_status"
                    item-text="text"
                    item-value="value"
                    class="ma-0 pa-0"
                    hide-details
                    v-if="editedIndex == index"
                  ></v-select>
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
                        : ''
                    "
                  >
                    {{ item.status }}
                  </v-chip>
                </template>
                <template v-slot:item.actions="{ item, index }">
                  <v-menu offset-y v-if="editedIndex != index">
                    <template v-slot:activator="{ on, attrs }">
                      <v-btn x-small v-bind="attrs" v-on="on">
                        Actions
                        <v-icon small> mdi-menu-down </v-icon>
                      </v-btn>
                    </template>
                    <v-list>
                      <v-list-item>
                        <v-list-item-title>
                          <v-btn
                            x-small
                            width="100px"
                            color="success"
                            @click="editProject(item)"
                          >
                            <v-icon small class="mr-2"> mdi-pencil </v-icon>
                            Edit
                          </v-btn>
                        </v-list-item-title>
                      </v-list-item>
                      <v-divider class="ma-0"></v-divider>
                      <v-list-item>
                        <v-list-item-title>
                          <v-btn
                            x-small
                            width="100px"
                            color="primary"
                            @click="(editedItem = item) + (dialog = true)"
                          >
                            <v-icon small class="mr-2"> mdi-plus </v-icon>
                            Remarks
                          </v-btn>
                        </v-list-item-title>
                      </v-list-item>
                      <v-divider class="ma-0"></v-divider>
                      <v-list-item>
                        <v-list-item-title>
                          <v-btn
                            x-small
                            width="100px"
                            color="info"
                            @click="viewProjectLogs(item)"
                          >
                            <v-icon small class="mr-2"> mdi-eye </v-icon>
                            View
                          </v-btn>
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>

                  <v-btn
                    x-small
                    color="primary"
                    v-if="editedIndex == index"
                    @click="updateReportStatus()"
                  >
                    save
                  </v-btn>
                  <v-btn
                    x-small
                    color="secondary"
                    v-if="editedIndex == index"
                    @click="editedIndex = -1"
                  >
                    cancel
                  </v-btn>
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
import moment from "moment";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";

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
  mixins: [validationMixin],

  validations: {
    remarks_date: { required },
    remarks_time: { required },
    remarks: { required },
  },
  data() {
    return {
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
        },
      ],
      search: "",
      search_report_status: "",
      filter_project_by_programmer: "",
      headers: [
        {
          text: "Approved/ Filing Date",
          value: "date_approved",
          sortable: false,
          width: "100px",
        },
        { text: "Ref No.", value: "ref_no", sortable: false },
        { text: "Report Title", value: "report_title", sortable: false },
        { text: "Ideal", value: "ideal", sortable: false },
        { text: "Department", value: "department", sortable: false },
        { text: "Manager", value: "manager", sortable: false },
        { text: "Date Received", value: "date_received", sortable: false },
        {
          text: "Template %",
          value: "template_percent",
          sortable: false,
          width: "100px",
        },
        {
          text: "Program Date",
          value: "program_date",
          sortable: false,
          width: "150px",
        },
        {
          text: "Program %",
          value: "program_percent",
          sortable: false,
          width: "100px",
        },
        {
          text: "Validation Date",
          value: "validation_date",
          sortable: false,
          width: "150px",
        },
        {
          text: "Validation %",
          value: "validation_percent",
          sortable: false,
          width: "100px",
        },
        { text: "Validator", value: "validator", sortable: false },
        { text: "Report Type", value: "type", sortable: false },
        { text: "Validation hrs.", value: "validate_hrs", sortable: false },
        { text: "Program hrs.", value: "program_hrs", sortable: false },
        {
          text: "Validation hrs. (This Month)",
          value: "",
          sortable: false,
          width: "120px",
        },
        {
          text: "Program hrs.  (This Month)",
          value: "",
          sortable: false,
          width: "120px",
        },
        {
          text: "Report Status",
          value: "status",
          sortable: false,
          width: "170px",
        },
        { text: "Actions", value: "actions", sortable: false, width: "80px" },
      ],
      input_program_date: false,
      input_validation_date: false,
      program_date: "",
      validation_date: "",
      disabled: false,
      dialog: false,
      projects: [],
      project_logs: [],
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
        template_percent: "",
        program_date: "",
        program_percent: "",
        validation_date: "",
        validation_percent: "",
      },
      defaultItem: {
        ref_no: "",
        report_title: "",
        template_percent: "",
        program_date: "",
        program_percent: "",
        validation_date: "",
        validation_percent: "",
      },
      permissions: {
        project_list: false,
        project_create: false,
        project_edit: false,
        project_delete: false,
      },
      loading: true,
      searchReportStatus: [
        { text: "All", value: "" },
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
      ],
      report_status: [
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
        { text: "Cancelled", value: "Cancelled" },
      ],
      status: [],
      input_remarks_date: false,
      time_modal: false,
      remarks_date: "",
      remarks_time: "",
      remarks: "",
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
      }).then((response) => {
        console.log(response.data);
        this.projects = response.data.projects;
        this.departments = response.data.departments;
        this.programmers = response.data.programmers;
        this.validators = response.data.validators;
        this.loading = false;

        if (this.user_type == "Admin") {
          this.filter_project_by_programmer = this.programmers[0].id;
        } else {
          this.filter_project_by_programmer = parseInt(this.user_id);
        }

        this.computeProgramHours;
        this.computeValidateHours;
      });
    },

    editProject(item) {
      let program_date = "";
      let validation_date = "";

      this.editedIndex = this.filteredProjects.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.program_date = "";
      this.validation_date = "";
      if (item.program_date) {
        program_date = item.program_date.split("/");
        this.program_date =
          program_date[2] + "-" + program_date[0] + "-" + program_date[1];
      }
      if (item.validation_date) {
        validation_date = item.validation_date.split("/");
        this.validation_date =
          validation_date[2] +
          "-" +
          validation_date[0] +
          "-" +
          validation_date[1];
      }
    },
    addRemarks() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.overlay = true;
        this.disabled = true;
        const data = {
          project_id: this.editedItem.id,
          remarks_date: this.remarks_date,
          remarks_time: this.remarks_time,
          remarks: this.remarks,
          status: this.editedItem.status,
        };

        Axios.post("/api/project_log/store", data, {
          headers: {
            Authorization: "Bearer " + access_token,
          },
        }).then(
          (response) => {
            if (response.data.success) {
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
      }
    },
    viewProjectLogs(item) {
      this.$router.push({
        name: "project.logs",
        params: { project_id: item.project_id },
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
      this.editedItem = this.defaultItem;
      this.editedIndex = -1;
      this.program_date = "";
      this.validation_date = "";
      this.report_status = [
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
        { text: "Cancelled", value: "Cancelled" },
      ];
      this.remarks_date = "";
      this.remarks_time = "";
      this.remarks = "";
    },

    updateReportStatus() {
      this.overlay = true;
      Axios.post("/api/project/update_status", this.editedItem, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          if (response.data.success) {
            this.overlay = false;
            Object.assign(
              this.filteredProjects[this.editedIndex],
              this.editedItem
            );
            this.showAlert();
            this.clear();
          }
        },
        (error) => {
          this.overlay = false;
          console.log(error);
        }
      );
    },
    formatDate(date) {
      if (!date) return null;

      const [year, month, day] = date.split("-");
      return `${month}/${day}/${year}`;
    },
  },
  computed: {
    filteredProjects() {
      let filteredProjects = [];

      this.projects.forEach((value, index) => {
        if (this.search_report_status == value.status) {
          if (this.filter_project_by_programmer == value.programmer_id) {
            filteredProjects.push(value);
          } else {
            filteredProjects.push(value);
          }
        }
      });

      if (!this.search_report_status) {
        this.projects.forEach((value, index) => {
          if (this.filter_project_by_programmer == value.programmer_id) {
            filteredProjects.push(value);
          }
        });
      }

      this.report_status = [
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
        { text: "Cancelled", value: "Cancelled" },
      ];

      return filteredProjects;
    },
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

    computedProgramDateFormatted() {
      this.editedItem.program_date = this.formatDate(this.program_date);
      return this.editedItem.program_date;
    },
    computedValidationDateFormatted() {
      this.editedItem.validation_date = this.formatDate(this.validation_date);
      return this.editedItem.validation_date;
    },
    computedRemarksDateFormatted() {
      return this.formatDate(this.remarks_date);
    },
    remarks_dateErrors() {
      const errors = [];
      if (!this.$v.remarks_date.$dirty) return errors;
      !this.$v.remarks_date.required &&
        errors.push("Remarks Date is required.");
      return errors;
    },
    remarks_timeErrors() {
      const errors = [];
      if (!this.$v.remarks_time.$dirty) return errors;
      !this.$v.remarks_time.required &&
        errors.push("Remarks Time is required.");
      return errors;
    },
    remarksErrors() {
      const errors = [];
      if (!this.$v.remarks.$dirty) return errors;
      !this.$v.remarks.required && errors.push("Remarks is required.");
      return errors;
    },
    
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getProject();
  },
};
</script>