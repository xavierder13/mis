<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
      <v-main>
        <v-card>
          <v-card-title>
            Programmer Projects
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              hide-details
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
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>

                <v-dialog v-model="dialog" max-width="500px">
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>

                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col class="pa-0 ma-0">
                            <v-text-field
                              name="ref_no"
                              v-model="editedItem.ref_no"
                              label="Reference #"
                              readonly
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="pa-0 ma-0">
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
                          <v-col class="pa-0 ma-0">
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
                          <v-col class="pa-0 ma-0">
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
                            ></v-autocomplete>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="pa-0 ma-0">
                            <v-autocomplete
                              name="validator"
                              v-model="editedItem.validator_id"
                              :items="validators"
                              item-value="id"
                              item-text="name"
                              label="Validator"
                            ></v-autocomplete>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="pa-0 ma-0 mb-4">
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
                        </v-row>
                        <v-row>
                          <v-col class="pa-0 ma-0">
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
                          <v-col class="pa-0 ma-0">
                            <v-text-field
                              name="ideal"
                              v-model="editedItem.ideal"
                              label="Ideal"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="pa-0 ma-0">
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
            :items="projects"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editProject(item)"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
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
      report_title: { required },
      department_id: { required },
      programmer_id: { required },
      type: { required },
    },
  },
  data() {
    return {
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
        { text: "Report Type", value: "type" },
        { text: "Ideal", value: "ideal" },
        { text: "Actions", value: "actions", sortable: false },
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
        { text: "Change Order", value: "Change Order"  }
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
        ideal: "",
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
        ideal: "",
      },
      permissions: {
        project_list: false,
        project_create: false,
        project_edit: false,
        project_delete: false,
      },
      loading: true,
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
      });
    },

    editProject(item) {
      this.editedIndex = this.projects.indexOf(item);
      this.editedItem = Object.assign({}, item);
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

          const project_id = item.id;
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
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedItem;
          const project_id = this.editedItem.id;

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

              this.disabled = false;
            },
            (error) => {
              console.log(error);
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
              console.log(response.data);
              if (response.data.success) {
                this.showAlert();
                this.close();

                //push recently added data from database
                this.projects.unshift(response.data.project);
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
    clear() {
      this.$v.$reset();
      this.editedItem.ref_no = "";
      this.editedItem.report_title = "";
      this.department = "";
      this.department_id = "";
      this.manager = "";
      this.programmer = "";
      this.programmer_id = "";
      this.validator = "";
      this.validator_id = "";
      this.date_received = "";
      this.date_approved = "";
      this.type = "";
      this.ideal = "";
    },
    getRefNumber() {
      Axios.get("/api/project/get_ref_no", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          let ref_no = response.data;
          console.log(ref_no);
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

      for(let [key, val] of this.departments.entries())
      {
        if(department_id == val.id)
        {
          this.editedItem.department = val.name;
        }
      }
    },
    programmerOnChange() {
      let programmer_id = this.editedItem.programmer_id;

      for(let [key, val] of this.programmers.entries())
      {
        if(programmer_id == val.id)
        {
          this.editedItem.programmer = val.name;
        }
      }
    },
    validatorOnChange() {
      let validator_id = this.editedItem.validator_id;

      for(let [key, val] of this.validators.entries())
      {
        if(validator_id == val.id)
        {
          this.editedItem.validator = val.name;
        }
      }
    }

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
      !this.$v.editedItem.type.required &&
        errors.push("Type is required.");
      return errors;
    },
    
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getProject();
  },
};
</script>