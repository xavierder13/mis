<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
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
            {{ project.ref_no + " - " + project.report_title }}
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
                      (editedItem.status = project.status)
                  "
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>
                <v-dialog v-model="dialog" max-width="700px">
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>

                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                            <v-select
                              v-model="editedItem.status"
                              :items="report_status"
                              item-text="text"
                              item-value="value"
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
                          <v-col cols="6">
                            <v-dialog
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
                            </v-dialog>
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
            loading-text="Loading... Please wait"
          >
            <template v-slot:item.status="{ item, index }">
              <v-chip
                v-if="editedIndex != index"
                :color="item.status == 'For Validation' ? 'info' : 'secondary'"
              >
                {{ item.status }}
              </v-chip>
            </template>
            <template v-slot:item.actions="{ item, index }">
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

export default {
  mixins: [validationMixin],

  validations: {
    editedItem: {
      status: { required },
      remarks_date: { required },
      remarks_time: { required },
      remarks: { required },
    },
  },
  data() {
    return {
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
        { text: "Date", value: "remarks_date", sortable: false },
        { text: "Time", value: "remarks_time", sortable: false },
        { text: "Status", value: "status", sortable: false },
        { text: "Remarks", value: "remarks", sortable: false },
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
        remarks_date: "",
        remarks_time: "",
        remarks: "",
      },
      defaultItem: {
        status: "",
        remarks_date: "",
        remarks_time: "",
        remarks: "",
      },
      permissions: {
        project_log_list: false,
        project_log_create: false,
        project_log_edit: false,
        project_log_delete: false,
      },
      loading: true,
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
      }).then((response) => {
        console.log(response.data);
        this.project = response.data.project;
        this.project_logs = response.data.project_logs;
        this.loading = false;
      });
    },
    save() {
      this.$v.$touch();
      
      if (!this.$v.$error) {
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
                this.showAlert();
                this.close();
              }

              this.disabled = false;
            },
            (error) => {
              console.log(error);
              this.disabled = false;
            }
          );
        } else {
          this.editedItem.project_id = this.project.id;

          Axios.post("/api/project_log/store", this.editedItem, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              console.log(response.data);
              if (response.data.success) {
                this.project_logs.push(response.data.project_log);
                this.showAlert();
                this.close();
              }

              this.disabled = false;
            },
            (error) => {
              console.log(error);
              this.disabled = false;
            }
          );
        }
      }
    },

    editProjectLog(item) {
      let remarks_date = "";
      this.dialog = true;
      this.editedIndex = this.project_logs.indexOf(item);
      this.editedItem = Object.assign({}, item);
      if (item.remarks_date) {
        remarks_date = item.remarks_date.split("/");
        this.editedItem.remarks_date = remarks_date[2] + "-" + remarks_date[0] + "-" + remarks_date[1];
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
      this.report_status = [
        { text: "For Validation", value: "For Validation" },
        { text: "Ongoing", value: "Ongoing" },
        { text: "Pending", value: "Pending" },
        { text: "Accepted", value: "Accepted" },
        { text: "Cancelled", value: "Cancelled" },
      ];
    },

    formatDate(date) {
      if (!date) return null;

      const [year, month, day] = date.split("-");
      return `${month}/${day}/${year}`;
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
      !this.$v.editedItem.remarks_time.required &&
        errors.push("Remarks Time is required.");
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
  },
};
</script>