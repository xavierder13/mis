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

            {{ user_type == "Programmer" ? "My Projects" : "" }}

            <v-divider
              vertical
              class="ml-2"
              v-if="permissions.print_preview"
            ></v-divider>

            <v-icon
              class="ml-2"
              :disabled="printDisabled"
              color="primary"
              @click="printPreview()"
              v-if="permissions.print_preview"
            >
              mdi-printer
            </v-icon>

            <v-divider
              vertical
              class="ml-2"
              v-if="permissions.export_project"
            ></v-divider>

            <export-excel
              class="btn btn-default"
              :data="filteredProjects"
              :fields="json_fields"
              worksheet="My Worksheet"
              name="filename.xls"
              v-if="permissions.export_project"
            >
              <v-icon
                :disabled="printDisabled"
                color="success"
                v-if="permissions.export_project"
              >
                mdi-file-excel
              </v-icon>
            </export-excel>

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
            <v-spacer></v-spacer>
            <v-menu
              v-model="input_filter_date"
              :close-on-content-click="false"
              transition="scale-transition"
              offset-y
              max-width="290px"
              min-width="290px"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-text-field
                  name="filter_date"
                  v-model="computedFilterDateFormatted"
                  label="As Of"
                  hint="MM/DD/YYYY"
                  persistent-hint
                  prepend-icon="mdi-calendar"
                  readonly
                  v-bind="attrs"
                  v-on="on"
                ></v-text-field>
              </template>
              <v-date-picker
                v-model="filter_date"
                no-title
                @input="input_filter_date = false + calculateHoursByDate()"
              ></v-date-picker>
            </v-menu>
            <template>
              <v-toolbar flat>
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
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-select
                              v-model="status"
                              label="Report Status"
                              :items="report_status"
                              item-text="text"
                              item-value="value"
                              :readonly="editedIndex > -1 ? true : false"
                            ></v-select>
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
                <v-dialog v-model="dialog2" max-width="700px">
                  <v-card>
                    <v-card-title>
                      <span class="headline">Update Report Percentage</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col>
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
                              v-bind:label="'Template'"
                            >
                            </v-text-field-money>
                          </v-col>
                          <v-col>
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
                              v-bind:label="'Programming'"
                            >
                            </v-text-field-money>
                          </v-col>
                          <v-col>
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
                              v-bind:label="'Validation'"
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
                        class="mb-4 mr-4"
                        :disabled="disabled"
                        @click="updateReportPercentage()"
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
                item-key="project_id"
                group-by="report_grp"
                class="elevation-1"
                :expanded.sync="expanded"
                loading-text="Loading... Please wait"
                fixed-header
              >
                <template
                  v-slot:group.header="{ items, headers, toggle, isOpen }"
                >
                  <td :colspan="headers.length">
                    <v-btn
                      @click="toggle"
                      small
                      icon
                      :ref="items"
                      :data-open="isOpen"
                    >
                      <v-icon v-if="isOpen">mdi-chevron-up</v-icon>
                      <v-icon v-else>mdi-chevron-down</v-icon>
                    </v-btn>
                    <v-chip
                      :color="
                        items[0].status == 'For Validation'
                          ? 'info'
                          : items[0].status == 'Ongoing'
                          ? 'secondary'
                          : items[0].status == 'Pending'
                          ? 'warning'
                          : items[0].status == 'Accepted'
                          ? 'success'
                          : ''
                      "
                    >
                      <strong>{{ items[0].status.toUpperCase() }}</strong>
                    </v-chip>
                  </td>
                </template>
                <!-- <template v-slot:item.template_percent="{ item, index }">
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
                    v-if="editedIndex == filteredProjects.indexOf(item)"
                  >
                  </v-text-field-money>

                  {{
                    item.template_percent &&
                    editedIndex != filteredProjects.indexOf(item)
                      ? item.template_percent + "%"
                      : ""
                  }}
                </template> -->
                <!-- <template v-slot:item.program_date="{ item, index }">
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
                </template> -->
                <!-- <template v-slot:item.program_percent="{ item, index }">
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
                    v-if="editedIndex == filteredProjects.indexOf(item)"
                  >
                  </v-text-field-money>
                  {{
                    item.program_percent &&
                    editedIndex != filteredProjects.indexOf(item)
                      ? item.program_percent + "%"
                      : ""
                  }}
                </template> -->
                <!-- <template v-slot:item.validation_date="{ item, index }">
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
                </template> -->
                <!-- <template v-slot:item.validation_percent="{ item, index }">
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
                    v-if="editedIndex == filteredProjects.indexOf(item)"
                  >
                  </v-text-field-money>
                  {{
                    item.validation_percent &&
                    editedIndex != filteredProjects.indexOf(item)
                      ? item.validation_percent + "%"
                      : ""
                  }}
                </template> -->
                <template v-slot:item.status="{ item, index }">
                  <!-- <v-select
                    v-model="editedItem.status"
                    :items="report_status"
                    item-text="text"
                    item-value="value"
                    class="ma-0 pa-0"
                    hide-details
                    v-if="editedIndex == index"
                  ></v-select> -->
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
                        : ''
                    "
                  >
                    {{ item.status }}
                  </v-chip>
                </template>
                <template
                  v-slot:item.actions="{ item, index }"
                  v-if="
                    permissions.project_edit ||
                    permissions.project_log_list ||
                    permissions.project_log_create
                  "
                >
                  <v-menu
                    offset-y
                    v-if="editedIndex != filteredProjects.indexOf(item)"
                  >
                    <template v-slot:activator="{ on, attrs }">
                      <v-btn x-small v-bind="attrs" v-on="on">
                        Actions
                        <v-icon small> mdi-menu-down </v-icon>
                      </v-btn>
                    </template>
                    <v-list>
                      <v-list-item v-if="permissions.project_edit">
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
                      <v-divider
                        class="ma-0"
                        v-if="permissions.project_log_create"
                      ></v-divider>
                      <v-list-item
                        v-if="
                          item.status != 'Accepted' &&
                          permissions.project_log_create
                        "
                      >
                        <v-list-item-title>
                          <v-btn
                            x-small
                            width="100px"
                            color="primary"
                            @click="createRemarks(item)"
                          >
                            <v-icon small class="mr-2"> mdi-plus </v-icon>
                            Remarks
                          </v-btn>
                        </v-list-item-title>
                      </v-list-item>
                      <v-divider
                        class="ma-0"
                        v-if="permissions.project_log_list"
                      ></v-divider>
                      <v-list-item v-if="permissions.project_log_list">
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

                  <!-- <v-btn
                    x-small
                    color="primary"
                    v-if="editedIndex == filteredProjects.indexOf(item)"
                    @click="updateReportPercentage()"
                  >
                    save
                  </v-btn>
                  <v-btn
                    x-small
                    color="secondary"
                    v-if="editedIndex == filteredProjects.indexOf(item)"
                    @click="editedIndex = -1"
                  >
                    cancel
                  </v-btn> -->
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
    remarks_date: { required },
    remarks_time: { required },
    remarks: { required },
    file:{ required },
  },
  data() {
    return {
      json_fields: {
        "Approved/ Filing Date": "date_approved",
        "Date Accepted": "accepted_date",
        "Ref No.": "ref_no",
        "Report Title": "report_title",
        "Ideal Prog Hrs.": "ideal_prog_hrs",
        "Ideal Valid Hrs.": "ideal_valid_hrs",
        Department: "department",
        Manager: "manager",
        "Date Received": "date_received",
        "Template %": "template_percent",
        "Program Date": "program_date",
        "Program %": "program_percent",
        "Validation Date": "validation_date",
        "Validation %": "validation_percent",
        Validator: "validator",
        "Report Type": "type",
        "Validation hrs.": "validate_hrs",
        "Program hrs.": "program_hrs",
        "Validation hrs. (This Month)": "validate_hrs_tm",
        "Program hrs.  (This Month)": "program_hrs_tm",
        "Report Status": "status",
      },
      json_meta: [
        [
          {
            key: "charset",
            value: "utf-8",
          },
        ],
      ],
      expanded: [],
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
      filter_date: new Date().toISOString().substr(0, 10),
      headers: [
        {
          text: "Report Group",
          value: "report_grp",
          width: "80px",
          sortable: false,
        },

        { text: "Actions", value: "actions", width: "80px", sortable: false },
        {
          text: "Approved/ Filing Date",
          value: "date_approved",
          width: "100px",
          sortable: false,
        },
        {
          text: "Date Accepted",
          value: "accepted_date",
          width: "100px",
          sortable: false,
        },
        { text: "Ref No.", value: "ref_no", sortable: false },
        { text: "Report Title", value: "report_title", sortable: false },
        { text: "Ideal Prog Hrs.", value: "ideal_prog_hrs", sortable: false },
        { text: "Ideal Valid Hrs.", value: "ideal_valid_hrs", sortable: false },
        { text: "Department", value: "department", sortable: false },
        { text: "Manager", value: "manager", sortable: false },
        { text: "Date Received", value: "date_received", sortable: false },
        {
          text: "Template %",
          value: "template_percent",
          width: "100px",
          sortable: false,
        },
        {
          text: "Program Date",
          value: "program_date",
          width: "150px",
          sortable: false,
        },
        {
          text: "Program %",
          value: "program_percent",
          width: "100px",
          sortable: false,
        },
        {
          text: "Validation Date",
          value: "validation_date",
          width: "150px",
          sortable: false,
        },
        {
          text: "Validation %",
          value: "validation_percent",
          width: "100px",
          sortable: false,
        },
        { text: "Validator", value: "validator", sortable: false },
        { text: "Report Type", value: "type", sortable: false },
        { text: "Validation hrs.", value: "validate_hrs", sortable: false },
        { text: "Program hrs.", value: "program_hrs", sortable: false },
        {
          text: "Validation hrs. (This Month)",
          value: "validate_hrs_tm",
          width: "120px",
          sortable: false,
        },
        {
          text: "Program hrs.  (This Month)",
          value: "program_hrs_tm",
          width: "120px",
          sortable: false,
        },
        {
          text: "Report Status",
          value: "status",
          width: "170px",
          sortable: false,
        },
      ],
      input_filter_date: false,
      input_program_date: false,
      input_validation_date: false,
      program_date: "",
      validation_date: "",
      disabled: false,
      dialog: false,
      dialog2: false,
      projects: [],
      project_logs: [],
      logs_per_project: [],
      project_execution_hrs: [],
      departments: [],
      programmers: [],
      validators: [],
      holidays: [],
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
      permissions: Home.data().permissions,
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
      status: "",
      remarks_date: new Date().toISOString().substr(0, 10),
      remarks_time: new Date().toTimeString().substr(0, 5),
      remarks: "",
      user: localStorage.getItem("user"),
      user_type: localStorage.getItem("user_type"),
      user_id: localStorage.getItem("user_id"),
      printDisabled: true,
    };
  },

  methods: {
    getProject() {
      this.loading = true;
      const data = { filter_date: this.filter_date };
      Axios.post("/api/project/programmer_reports", data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          // console.log(response.data);
          this.printDisabled = false;
          this.projects = response.data.projects;
          this.project_logs = response.data.project_logs;
          this.project_execution_hrs = response.data.project_execution_hrs;
          this.departments = response.data.departments;
          this.programmers = response.data.programmers;
          this.validators = response.data.validators;
          this.holidays = response.data.holidays;
          this.loading = false;

          if (this.user_type == "Admin") {
            this.filter_project_by_programmer = this.programmers[0].id;
          } else {
            this.filter_project_by_programmer = parseInt(this.user_id);
          }

          this.project_execution_hrs.forEach((value, index) => {
            this.filteredProjects.forEach((val, index) => {
              if (value.project_id == val.project_id) {
                // execution hrs overall
                this.filteredProjects[index].program_hrs =
                  value.execution_hrs.program_hrs;
                this.filteredProjects[index].validate_hrs =
                  value.execution_hrs.validate_hrs;

                // execution hrs this month
                this.filteredProjects[index].program_hrs_tm =
                  value.execution_hrs_tm.program_hrs;
                this.filteredProjects[index].validate_hrs_tm =
                  value.execution_hrs_tm.validate_hrs;
              }
            });
          });
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
      this.dialog2 = true;
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
      let project_id = this.editedItem.project_id;
      if (!this.$v.$error) {
        Axios.get("/api/project_log/get_latest_log/" + project_id, {
          headers: {
            Authorization: "Bearer " + access_token,
          },
        }).then((response) => {
          // console.log(response.data);
          let latest_log = response.data.latest_log;

          // if last remarks has turnover status then show warning message
          if (latest_log.turnover && this.status != this.editedItem.status) {
            this.showConfirmAlert(this.status);
          } else {
            this.storeRemarks();
          }
        });
      }
    },
    storeRemarks() {
      let project_id = this.editedItem.project_id;
      this.overlay = true;
      this.disabled = true;
      const data = {
        project_id: project_id,
        remarks_date: this.remarks_date,
        remarks_time: this.remarks_time,
        remarks: this.remarks,
        status: this.status,
      };
      Axios.post("/api/project_log/project_turnover", data, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          console.log(response.data);
          if (response.data.success) {
            this.getProject();
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
      this.dialog2 = false;
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
      (this.remarks_date = new Date().toISOString().substr(0, 10)),
        (this.remarks_time = new Date().toTimeString().substr(0, 5)),
        (this.remarks = "");
    },

    updateReportPercentage() {
      this.overlay = true;
      Axios.post("/api/project/update_status", this.editedItem, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          console.log(response.data);
          if (response.data.success) {
            this.overlay = false;
            Object.assign(
              this.filteredProjects[this.editedIndex],
              this.editedItem
            );
            this.showAlert();
            this.clear();
            this.dialog2 = false;
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
    calculateHoursByDate() {
      this.getProject();
    },
    createRemarks(item) {
      this.editedItem = item;
      this.dialog = true;
      this.status = item.status;
      this.getLogsPerProject(item);
      this.setStatusSelectItems(item);
    },
    getLogsPerProject(item) {
      this.logs_per_project = [];
      this.project_logs.forEach((value, index) => {
        if (item.project_id == value.project_id) {
          this.logs_per_project.push(value);
        }
      });
    },
    setStatusSelectItems(item) {
      let hasOngoingTurnover = false;
      let last_log_status = "";
      // if logs has Ongoing and Turnover status

      this.logs_per_project.forEach((val, i) => {
        if (val.status == "Ongoing" && val.turnover == "Y") {
          hasOngoingTurnover = true;
        }

        // get the last index of status except "Pending"
        if (
          (val.status == "Ongoing" && val.turnover) ||
          (val.status == "For Validation" && val.turnover)
        ) {
          last_log_status = val.status;
        }
      });

      if (hasOngoingTurnover) {
        if (item.status == "Pending") {
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
        } else {
          this.report_status = [
            { text: "For Validation", value: "For Validation" },
            { text: "Ongoing", value: "Ongoing" },
            { text: "Pending", value: "Pending" },
            { text: "Accepted", value: "Accepted" },
            { text: "Cancelled", value: "Cancelled" },
          ];
        }
      } else {
        this.report_status = [
          { text: "Ongoing", value: "Ongoing" },
          { text: "Pending", value: "Pending" },
          { text: "Cancelled", value: "Cancelled" },
        ];
      }
    },
    showConfirmAlert(status) {
      this.$swal({
        title: "Are you sure?",
        text: "You don't have starting date and time '" + status + "' log",
        icon: "info",
        showCancelButton: true,
        confirmButtonColor: "primary",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Turnover Project",
      }).then((result) => {
        if (result.value) {
          this.storeRemarks();
        }
      });
    },
    printPreview() {
      window.open(
        location.origin +
          "/reports_preview?programmer_id=" +
          this.filter_project_by_programmer +
          "&filter_date=" +
          this.filter_date,
        "_blank"
      );
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
      this.permissions.programmer_projects = Home.methods.hasPermission([
        "programmer-projects",
      ]);
      this.permissions.print_preview = Home.methods.hasPermission([
        "print-preview",
      ]);
      this.permissions.export_project = Home.methods.hasPermission([
        "export-project",
      ]);
      this.permissions.project_log_list = Home.methods.hasPermission([
        "project-log-list",
      ]);
      this.permissions.project_log_create = Home.methods.hasPermission([
        "project-log-create",
      ]);
      this.permissions.project_edit = Home.methods.hasPermission([
        "project-edit",
      ]);

      // hide column actions if user has no permission
      if (
        !this.permissions.project_log_list &&
        !this.permissions.project_log_create &&
        !this.permissions.project_edit
      ) {
        this.headers[1].align = " d-none";
      }
      else
      {
        this.headers[1].align = "";
      }

      // if user is not authorize
      if (!this.permissions.programmer_projects) {
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

        if(action == 'project-create' || action == 'project-edit' || action == 'project-delete' || action == 'import-project')
        {
          this.getProject();
        }

      });
    },
  },
  computed: {
    filteredProjects() {
      let filter_date = new Date(this.filter_date);
      let firstDay = new Date(
        filter_date.getFullYear(),
        filter_date.getMonth(),
        1
      );
      let lastDay = new Date(
        filter_date.getFullYear(),
        filter_date.getMonth() + 1,
        0
      );

      let filteredProjects = [];
      let filteredAcceptedProjects = [];

      this.projects.forEach((value, index) => {
        if (this.search_report_status == value.status) {
          if (this.filter_project_by_programmer == value.programmer_id) {
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

      filteredProjects.forEach((value, index) => {
        let accepted_date = new Date(value.accepted_date);

        if (
          (accepted_date >= firstDay && accepted_date <= lastDay) ||
          !value.accepted_date
        ) {
          filteredAcceptedProjects.push(value);
        }
      });

      this.report_status = [
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
        { text: "Cancelled", value: "Cancelled" },
      ];

      return filteredAcceptedProjects;
    },

    formTitle() {
      return this.editedIndex === -1 ? "New Remarks" : "Edit Remarks";
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
    computedFilterDateFormatted() {
      return this.formatDate(this.filter_date);
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

    fileErrors() {
      const errors = [];
      if (!this.$v.file.$dirty) return errors;
      !this.$v.file.required && errors.push("File is required.");
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