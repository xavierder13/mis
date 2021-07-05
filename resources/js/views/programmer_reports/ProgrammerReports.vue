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
        <div class="d-flex flex-row-reverse mb-2">
          <div>
            <v-btn
              class="ml-1"
              :disabled="printDisabled"
              color="primary"
              small
              @click="printPreview()"
              v-if="permissions.print_preview"
            >
              <v-icon class="mr-1" small> mdi-eye </v-icon>
              Preview
            </v-btn>
          </div>
          <div>
            <export-excel
              class="btn btn-default pa-0 ma-0"
              :data="filteredProjects"
              :fields="json_fields"
              worksheet="My Worksheet"
              :name="filename"
              v-if="permissions.export_project"
            >
              <v-btn
                :disabled="printDisabled"
                color="success"
                small
                v-if="permissions.export_project"
              >
                <v-icon class="mr-1" small> mdi-microsoft-excel </v-icon>
                export
              </v-btn>
            </export-excel>
          </div>
        </div>

        <v-card>
          <v-card-title>
            <v-select
              v-model="filter_project_by_programmer"
              :items="programmers"
              item-text="name"
              item-value="id"
              label="Programmer"
              hide-details=""
              v-if="permissions.view_all_projects || user_type == 'Validator'"
            ></v-select>

            {{
              permissions.view_all_projects || user_type == "Validator"
                ? ""
                : "My Projects"
            }}

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
                              readonly
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-select
                              v-model="remarksItem.status"
                              label="Report Status"
                              :items="report_status"
                              item-text="text"
                              item-value="value"
                              :error-messages="statusErrors"
                              @change="$v.remarksItem.status.$touch()"
                              @blur="$v.remarksItem.status.$touch()"
                              :readonly="editedIndex > -1 ? true : false"
                              v-if="!endorse_project"
                            ></v-select>

                            <v-select
                              v-model="remarksItem.programmer_id"
                              :items="endorseProgrammerList"
                              item-text="name"
                              item-value="id"
                              label="Endorse To"
                              :error-messages="endorseProgrammerErrors"
                              @change="$v.remarksItem.programmer_id.$touch()"
                              @blur="$v.remarksItem.programmer_id.$touch()"
                              v-if="endorse_project"
                            ></v-select>
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
                                  @input="$v.remarksItem.remarks_date.$touch()"
                                  @blur="$v.remarksItem.remarks_date.$touch()"
                                ></v-text-field>
                              </template>
                              <v-date-picker
                                v-model="remarksItem.remarks_date"
                                no-title
                                @input="input_remarks_date = false"
                              ></v-date-picker>
                            </v-menu>
                          </v-col>
                          <v-col cols="2">
                            <!-- <v-dialog
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
                            </v-dialog> -->
                            <v-autocomplete
                              v-model="remarksItem.hour"
                              :items="hour"
                              label="Hour"
                              @blur="$v.remarksItem.hour.$touch()"
                              :hint="'24 Hr Format'"
                              persistent-hint
                            ></v-autocomplete>
                          </v-col>
                          <v-col cols="2">
                            <v-autocomplete
                              v-model="remarksItem.minute"
                              :items="minute"
                              label="Minute"
                              @blur="$v.remarksItem.minute.$touch()"
                            ></v-autocomplete>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-textarea
                              v-model="remarksItem.remarks"
                              label="Remarks"
                              rows="2"
                              :error-messages="remarksErrors"
                              @input="$v.remarksItem.remarks.$touch()"
                              @blur="$v.remarksItem.remarks.$touch()"
                            ></v-textarea>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>

                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn color="#E0E0E0" @click="close()" class="mb-4">
                        Cancel
                      </v-btn>
                      <v-btn
                        color="primary"
                        class="mb-4 mr-4"
                        :disabled="disabled"
                        @click="
                          endorse_project ? endorseProject() : addRemarks()
                        "
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
                <v-dialog v-model="dialog2" max-width="700px" persistent>
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
                      <span class="headline">Update Report Percentage</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col v-if="editedItem.endorse_project_id">
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
                                  hint="MM/DD/YYYY"
                                  persistent-hint
                                  prepend-icon="mdi-calendar"
                                  readonly
                                  v-bind="attrs"
                                  v-on="on"
                                  :error="date_receive_invalid"
                                ></v-text-field>
                              </template>
                              <v-date-picker
                                v-model="editedItem.date_receive"
                                no-title
                                @input="input_date_receive = false"
                              ></v-date-picker>
                            </v-menu>
                          </v-col>
                          <v-col v-if="permissions.edit_template_percentage">
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
                          <v-col v-if="permissions.edit_program_percentage">
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
                          <v-col v-if="permissions.edit_validate_percentage">
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
            <div style="width: 2800px">
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
                <template v-slot:item.template_percent="{ item, index }">
                  {{ item.template_percent ? item.template_percent + "%" : "" }}
                </template>
                <template v-slot:item.program_percent="{ item, index }">
                  {{ item.program_percent ? item.program_percent + "%" : "" }}
                </template>
                <template v-slot:item.validation_percent="{ item, index }">
                  {{
                    item.validation_percent ? item.validation_percent + "%" : ""
                  }}
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
                      v-model="editedItem.program_date"
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
                      v-model="editedItem.validation_date"
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
                        : 'error'
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
                    <v-list class="pa-1">
                      <v-list-item
                        class="ma-0 pa-0"
                        style="min-height: 25px"
                        v-if="
                          permissions.edit_program_percentage ||
                          permissions.edit_template_percentage ||
                          permissions.edit_validate_percentage ||
                          permissions.endorse_project
                        "
                      >
                        <v-list-item-title>
                          <v-btn
                            class="ma-1"
                            x-small
                            width="100px"
                            color="primary"
                            @click="editProject(item)"
                          >
                            <v-icon small class="mr-2"> mdi-pencil </v-icon>
                            Edit
                          </v-btn>
                        </v-list-item-title>
                      </v-list-item>

                      <v-list-item
                        class="ma-0 pa-0"
                        style="min-height: 25px"
                        v-if="
                          item.status != 'Accepted' &&
                          permissions.project_log_create
                        "
                      >
                        <v-list-item-title>
                          <v-btn
                            class="ma-1"
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

                      <v-list-item
                        class="ma-0 pa-0"
                        style="min-height: 25px"
                        v-if="permissions.project_log_list"
                      >
                        <v-list-item-title>
                          <v-btn
                            class="ma-1"
                            x-small
                            width="100px"
                            color="primary"
                            @click="viewProjectLogs(item)"
                          >
                            <v-icon small class="mr-2"> mdi-eye </v-icon>
                            View
                          </v-btn>
                        </v-list-item-title>
                      </v-list-item>

                      <v-list-item
                        class="ma-0 pa-0"
                        style="min-height: 25px"
                        v-if="
                          item.status != 'Accepted' &&
                          permissions.project_acceptance_overview
                        "
                      >
                        <v-list-item-title>
                          <v-btn
                            class="ma-1"
                            x-small
                            width="100px"
                            color="primary"
                            @click="createRemarks(item) + (endorse_project = true)"
                          >
                            <v-icon small class="mr-2"> mdi-folder-move </v-icon>
                            Endorse
                          </v-btn>
                        </v-list-item-title>
                      </v-list-item>

                      <v-list-item
                        class="ma-0 pa-0"
                        style="min-height: 25px"
                        v-if="
                          item.status == 'Accepted' &&
                          permissions.project_acceptance_overview
                        "
                      >
                        <v-list-item-title>
                          <v-btn
                            class="ma-1"
                            x-small
                            width="100px"
                            color="primary"
                            @click="createAcceptanceOverview(item)"
                          >
                            <v-icon small class="mr-2">
                              mdi-file-document-edit-outline
                            </v-icon>
                            Overview
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

import axios from "axios";
import moment from "moment";
import { validationMixin } from "vuelidate";
import {
  required,
  maxLength,
  email,
  requiredIf,
  requiredUnless,
} from "vuelidate/lib/validators";
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
    remarksItem: {
      status: { required },
      programmer_id: {
        required: requiredIf(function () {
          return this.programmerIsRequired;
        }),
      },
      remarks_date: { required },
      hour: { required },
      minute: { required },
      remarks: { required },
    },
  },

  data() {
    return {
      hour: [],
      minute: [],
      json_fields: {
        "Approved/ Filing Date": "date_approve",
        "Date Accepted": "accepted_date",
        "Ref No.": "ref_no",
        "Report Title": "report_title",
        "Ideal Prog Hrs.": "ideal_prog_hrs",
        "Ideal Valid Hrs.": "ideal_valid_hrs",
        Department: "department",
        Manager: "manager",
        "Date Received": "date_receive",
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
          value: "date_approve",
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
        { text: "Date Endored", value: "endorse_date", sortable: false },
        { text: "Date Received", value: "date_receive", sortable: false },
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
      input_date_receive: false,
      input_program_date: false,
      input_validation_date: false,
      date_receive: "",
      program_date: "",
      validation_date: "",
      disabled: false,
      disabledEndorse: false,
      dialog: false,
      dialog2: false,
      dialog_endorse: false,
      date_receive_invalid : false,
      projects: [],
      project: {},
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
      editedItem: {},
      defaultItem: {},
      remarksItem: {
        project_id: "",
        programmer_id: "",
        status: "",
        remarks_date: new Date().toISOString().substr(0, 10),
        remarks_time: "",
        hour: String(new Date().toTimeString().substr(0, 5).split(":")[0]),
        minute: String(new Date().toTimeString().substr(0, 5).split(":")[1]),
        remarks: "",
        turnover: "",
      },
      remarksDefault: {
        project_id: "",
        programmer_id: "",
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
      endorse_project: false,
      status: [],
      input_remarks_date: false,
      input_endorse_date: false,
      time_modal: false,
      endorse_date: new Date().toISOString().substr(0, 10),
      endorse_time: new Date().toTimeString().substr(0, 5),
      user: localStorage.getItem("user"),
      user_type: localStorage.getItem("user_type"),
      user_id: localStorage.getItem("user_id"),
      printDisabled: true,
      user_permissions: [],
      user_roles: [],
    };
  },

  methods: {
    getProject() {
      this.loading = true;
      const data = { filter_date: this.filter_date };
      axios.post("/api/project/programmer_reports", data).then(
        (response) => {
          this.printDisabled = false;
          this.projects = response.data.projects;
          this.project_logs = response.data.project_logs;
          this.project_execution_hrs = response.data.project_execution_hrs;
          this.departments = response.data.departments;
          this.programmers = response.data.programmers;
          this.validators = response.data.validators;
          this.holidays = response.data.holidays;
          this.loading = false;

          // console.log(response.data);
          
          // if dropdown programmer has no value(first load) then set a value
          if (!this.filter_project_by_programmer) {
            if (
              this.permissions.view_all_projects ||
              this.user_type == "Validator"
            ) {
              this.filter_project_by_programmer = parseInt(
                this.programmers[0] ? this.programmers[0].id : 0
              );
            } else {
              this.filter_project_by_programmer = parseInt(this.user_id);
            }
          }
   
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editProject(item) {
      
      this.dialog2 = true;
      let date_receive = "";
      let program_date = "";
      let validation_date = "";

      this.editedIndex = this.filteredProjects.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.program_date = "";
      this.validation_date = "";

      if (item.date_receive) {
        date_receive = item.date_receive.split("/");
        this.editedItem.date_receive =
          date_receive[2] + "-" + date_receive[0] + "-" + date_receive[1];
      }
      if (item.program_date) {
        program_date = item.program_date.split("/");
        this.editedItem.program_date =
          program_date[2] + "-" + program_date[0] + "-" + program_date[1];
      }
      if (item.validation_date) {
        validation_date = item.validation_date.split("/");
        this.editedItem.validation_date =
          validation_date[2] +
          "-" +
          validation_date[0] +
          "-" +
          validation_date[1];
      }
    },
    addRemarks() {
      this.$v.remarksItem.$touch();

      let project_id = this.editedItem.project_id;

      if (!this.$v.remarksItem.$error) {
        axios.get("/api/project_log/get_latest_log/" + project_id).then((response) => {
          let latest_log = response.data.latest_log;
          let latest_log_turnover = null;

          // if last remarks has turnover status then show warning message
          if (latest_log) {
            latest_log_turnover = latest_log.turnover;
          }

          if (
            latest_log_turnover &&
            this.remarksItem.status != this.editedItem.status
          ) {
            this.showConfirmAlert(this.remarksItem.status);
          } else {
            this.storeRemarks();
          }
        }, (error) => {
          this.isUnauthorized(error);
        });
      }
    },
    storeRemarks() {
      let project_id = this.editedItem.project_id;
      let endorse_project_id = this.editedItem.endorse_project_id;

      this.overlay = true;
      this.disabled = true;

      this.remarksItem.project_id = project_id;
      this.remarksItem.endorse_project_id = endorse_project_id;
      this.remarksItem.remarks_time =
        this.remarksItem.hour + ":" + this.remarksItem.minute;

      const data = this.remarksItem;

      axios.post("/api/project_log/project_turnover", data).then(
        (response) => {
          if (response.data.success) {
            // send data to Socket.IO Server
            this.$socket.emit("sendData", { action: "project-log-create" });
            let hasChanges = response.data.hasChanges;

            // if changed status is true
            if (hasChanges == true) {
              this.$socket.emit("sendData", { action: "project-edit" });
            }

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
    },
    viewProjectLogs(item) {
      let params_value = { project_id: item.project_id };
      let route_name = "project.logs";

      // if endorse_project_id has value
      if (item.endorse_project_id) {
        params_value = {
          project_id: item.project_id,
          endorse_project_id: item.endorse_project_id,
        };
        route_name = "endorse_project.logs";
      }

      this.$router.push({
        name: route_name,
        params: params_value,
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
      this.date_receive = "";
      this.program_date = "";
      this.validation_date = "";
      this.remarksItem = {
        project_id: "",
        programmer_id: "",
        status: "",
        remarks_date: new Date().toISOString().substr(0, 10),
        remarks_time: "",
        hour: String(new Date().toTimeString().substr(0, 5).split(":")[0]),
        minute: String(new Date().toTimeString().substr(0, 5).split(":")[1]),
        remarks: "",
        turnover: "",
      };
      this.endorse_project = false;
      this.date_receive_invalid = false;
    },

    updateReportPercentage() {

      let endorse_date = moment(new Date(this.editedItem.endorse_date), "YYYY-MM-DD");
      let date_receive = moment(new Date(this.editedItem.date_receive), "YYYY-MM-DD");
      
      // date_receive must be equal or greater than endorse_date
      if(date_receive < endorse_date)
      {
        this.date_receive_invalid = true;
      }

      if(this.date_receive_invalid)
      {
        this.$swal({
          position: "center",
          title: "Invalid Date",
          text: "Date Received must be greater than or equal to Date Endorsed",
          icon: "warning",
          showConfirmButton: true,
        });
      }
      else
      {
        this.overlay = true;
        axios.post("/api/project/update_status", this.editedItem).then(
          (response) => {
            if (response.data.success) {
              // send data to Socket.IO Server
              this.$socket.emit("sendData", { action: "project-edit" });

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
            this.isUnauthorized(error);
          }
        );
      }

      
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
      this.remarksItem.status = item.status;

      if (!this.endorse_project) {
        this.getLogsPerProject(item);
        this.setStatusSelectItems(item);
      }
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

      this.report_status = [
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
        { text: "Cancelled", value: "Cancelled" },
      ];

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

            //Ongoing item from array report_status
            this.report_status.splice(1, 1);

          } else if (last_log_status == "For Validation") {

            //For Validation item from array report_status
            this.report_status.splice(0, 1);

          }
        }
      } else {

        //Accepted item from array report_status
        this.report_status.splice(3, 1);
        
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

    endorseProject() {
      this.$v.remarksItem.$touch();

      if (!this.$v.remarksItem.$error) {
        this.$swal({
          title: "Are you sure?",
          text: "You won't be able to revert this!",
          icon: "info",
          showCancelButton: true,
          confirmButtonColor: "primary",
          cancelButtonColor: "#6c757d",
          confirmButtonText: "Proceed",
        }).then((result) => {
          if (result.value) {
            this.remarksItem.project_id = this.editedItem.project_id;
            this.remarksItem.remarks_time =
              this.remarksItem.hour + ":" + this.remarksItem.minute;

            const data = this.remarksItem;

            axios.post("/api/project/endorse_project", data).then(
              (response) => {
                if (response.data.success) {
                  // send data to Socket.IO Server
                  this.$socket.emit("sendData", { action: "project-edit" });

                  // send data to Socket.IO Server
                  this.$socket.emit("sendData", {
                    action: "project-log-create",
                  });

                  this.showAlert();
                  this.close();
                }
                this.disabledEndorse = false;
              },
              (error) => {
                this.isUnauthorized(error);
              }
            );
          }
        });
      }
    },

    createAcceptanceOverview(item) {
      let project_id = item.project_id;
      
      this.$router.push({
        name: "project_acceptance",
        params: { project_id: project_id },
      });
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

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },

    userRolesPermissions() {
      axios.get("api/user/roles_permissions").then((response) => {
        this.user_permissions = response.data.user_permissions;
        this.user_roles = response.data.user_roles;
        this.getRolesPermissions();
      });
    },

    getRolesPermissions() {
      this.permissions.programmer_projects = this.hasPermission([
        "programmer-projects",
      ]);
      this.permissions.print_preview = this.hasPermission([
        "print-preview",
      ]);
      this.permissions.export_project = this.hasPermission([
        "export-project",
      ]);
      this.permissions.project_log_list = this.hasPermission([
        "project-log-list",
      ]);
      this.permissions.project_log_create = this.hasPermission([
        "project-log-create",
      ]);
      this.permissions.project_edit = this.hasPermission([
        "project-edit",
      ]);
      this.permissions.view_all_projects = this.hasPermission([
        "view-all-projects",
      ]);
      this.permissions.edit_template_percentage = this.hasPermission([
        "edit-template-percentage",
      ]);
      this.permissions.edit_program_percentage = this.hasPermission([
        "edit-program-percentage",
      ]);
      this.permissions.edit_validate_percentage = this.hasPermission([
        "edit-validate-percentage",
      ]);
      this.permissions.endorse_project = this.hasPermission([
        "endorse-project",
      ]);
      this.permissions.project_acceptance_overview = this.hasPermission(
        ["project-acceptance-overview"]
      );

      // hide column actions if user has no permission
      if (
        !this.permissions.project_log_list &&
        !this.permissions.project_log_create &&
        !this.permissions.project_edit
      ) {
        this.headers[1].align = " d-none";
      } else {
        this.headers[1].align = "";
      }

      // if user is not authorize
      if (!this.permissions.programmer_projects) {
        this.$router.push("/unauthorize").catch(() => {});
      }
    },
    hasRole(roles) {
      let hasRole = false;

      roles.forEach((value, index) => {
        hasRole = this.user_roles.includes(value);
      });

      return hasRole;
    },

    hasPermission(permissions) {
      let hasPermission = false;

      permissions.forEach((value, index) => {
        hasPermission = this.user_permissions.includes(value);
      });

      return hasPermission;
    },
    websocket() {
      
      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;
        if (
          action == "user-edit" ||
          action == "role-edit" ||
          action == "role-delete" ||
          action == "permission-create" ||
          action == "permission-delete"
        ) {
          this.userRolesPermissions();
        }

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

      // filter record based on programmer dropdown value
      this.projects.forEach((value, index) => {
        if (this.search_report_status == value.status) {
          if (this.filter_project_by_programmer == value.programmer_id) {
            filteredProjects.push(value);
          }
        }
      });

      // if dropdown Report Status has no value
      if (!this.search_report_status) {
        this.projects.forEach((value, index) => {
          // if user has the permission to view all projects or user type is not validator
          if (
            this.permissions.view_all_projects ||
            this.user_type != "Validator"
          ) {
            if (this.filter_project_by_programmer == value.programmer_id) {
              filteredProjects.push(value);
            }
          } else {
            if (
              this.filter_project_by_programmer == value.programmer_id &&
              this.user_id == value.validator_id &&
              value.status != "Accepted"
            ) {
              filteredProjects.push(value);
            }
          }
        });
      }

      // filter accepted report based on parameter date
      // filteredProjects.forEach((value, index) => {
      //   let accepted_date = new Date(value.accepted_date);
      //   if (
      //     (accepted_date >= firstDay && accepted_date <= lastDay) ||
      //     !value.accepted_date
      //   ) {
      //     filteredAcceptedProjects.push(value);
      //   }
      // });

      this.report_status = [
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
        { text: "Cancelled", value: "Cancelled" },
      ];

      this.project_execution_hrs.forEach((value, index) => {
        filteredProjects.forEach((val, i) => {
          if (value.project_id == val.project_id) {
            // execution hrs overall
            filteredProjects[i].program_hrs =
              value.execution_hrs.program_hrs;
            filteredProjects[i].validate_hrs =
              value.execution_hrs.validate_hrs;

            // execution hrs this month
            filteredProjects[i].program_hrs_tm =
              value.execution_hrs_tm.program_hrs;
            filteredProjects[i].validate_hrs_tm =
              value.execution_hrs_tm.validate_hrs;
          }
        });
      });

      return filteredProjects;
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
    computedDateReceiveFormatted() {
      // return this.editedItem.date_receive;
      return this.formatDate(this.editedItem.date_receive);
    },
    computedProgramDateFormatted() {
      return this.editedItem.program_date;
      // return this.formatDate(this.editedItem.program_date);
    },
    computedValidationDateFormatted() {
      return this.editedItem.validation_date;
      // return this.formatDate(this.editedItem.validation_date);
    },
    computedRemarksDateFormatted() {
      return this.formatDate(this.remarksItem.remarks_date);
    },
    computedFilterDateFormatted() {
      return this.formatDate(this.filter_date);
    },
    statusErrors() {
      const errors = [];
      if (!this.$v.remarksItem.status.$dirty) return errors;
      !this.$v.remarksItem.status.required &&
        errors.push("Report Status is required.");
      return errors;
    },
    remarks_dateErrors() {
      const errors = [];
      if (!this.$v.remarksItem.remarks_date.$dirty) return errors;
      !this.$v.remarksItem.remarks_date.required &&
        errors.push("Remarks Date is required.");
      return errors;
    },
    remarks_timeErrors() {
      const errors = [];
      if (!this.$v.remarksItem.remarks_time.$dirty) return errors;
      !this.$v.remarksItem.remarks_time.required &&
        errors.push("Remarks Time is required.");
      return errors;
    },
    hourErrors() {
      const errors = [];
      if (!this.$v.remarksItem.hour.$dirty) return errors;
      !this.$v.remarksItem.hour.required && errors.push("Required.");

      if (this.timeHasError) {
        errors.push("Invalid");
      }

      return errors;
    },
    minuteErrors() {
      const errors = [];
      if (!this.$v.remarksItem.minute.$dirty) return errors;
      !this.$v.remarksItem.minute.required && errors.push("Required.");

      if (this.timeHasError) {
        errors.push("Invalid");
      }

      return errors;
    },
    remarksErrors() {
      const errors = [];
      if (!this.$v.remarksItem.remarks.$dirty) return errors;
      !this.$v.remarksItem.remarks.required &&
        errors.push("Remarks is required.");
      return errors;
    },
    endorseProgrammerErrors() {
      const errors = [];
      if (!this.$v.remarksItem.programmer_id.$dirty) return errors;
      !this.$v.remarksItem.programmer_id.required &&
        errors.push("Programmer is required.");
      return errors;
    },
    filename() {
      let filename = "";
      this.programmers.forEach((value, index) => {
        if (this.filter_project_by_programmer == value.id) {
          filename = value.name + " Report.xls";
        }
      });

      return filename;
    },
    endorseProgrammerList() {
      let programmers = [];
      this.programmers.forEach((value, index) => {
        if (
          this.user_id != value.id &&
          this.editedItem.programmer_id != value.id
        ) {
          programmers.push(value);
        }
      });
      return programmers;
    },
    programmerIsRequired() {
      let isRequired = false;
      if (this.endorse_project) {
        isRequired = true;
      } else {
        isRequired = false;
      }

      return isRequired;
    },
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getProject();
    this.userRolesPermissions();
    this.setDropdownTime();
    this.websocket();
  },
};
</script>