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
          <v-card-title class="mb-0 pb-0">
            Project Acceptance Overview
            <v-btn class="ml-4" color="info" small @click="printPreview()">
              <v-icon class="mr-1" small>mdi-eye</v-icon> Preview</v-btn
            ></v-card-title
          >
          <v-divider></v-divider>
          <v-card-text class="ml-4">
            <v-row>
              <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                <v-textarea
                  name="overview"
                  v-model="editedItem.overview"
                  :error-messages="overviewErrors"
                  label="Overview"
                  rows="3"
                  required
                ></v-textarea>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                <v-textarea
                  name="for_delete"
                  v-model="editedItem.for_delete"
                  label="For Delete"
                  rows="1"
                ></v-textarea>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="intended_users"
                  v-model="editedItem.intended_users"
                  label="Intended Users"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                <v-textarea
                  name="location1"
                  v-model="editedItem.location1"
                  label="Location Inside SAP B1 (if imported)"
                  rows="1"
                  required
                ></v-textarea>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                <v-textarea
                  name="location2"
                  v-model="editedItem.location2"
                  label="Location Outside SAP B1"
                  rows="1"
                  required
                ></v-textarea>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                <v-textarea
                  name="validator_note"
                  v-model="editedItem.validator_note"
                  label="Validator's Acceptance"
                  rows="2"
                  required
                ></v-textarea>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="6" class="mt-0 mb-0 pt-0 pb-0">
                <v-textarea
                  name="manager_note"
                  v-model="editedItem.manager_note"
                  label="Manager's Acceptance"
                  rows="2"
                  required
                ></v-textarea>
              </v-col>
            </v-row>
            <!-- <v-row>
              <v-col>
                <ckeditor
                  v-model="editorData"
                  :config="editorConfig"
                ></ckeditor>
              </v-col>
            </v-row> -->
          </v-card-text>
          <v-card-actions>
            <v-row>
              <v-col>
                <v-btn
                  class="ml-6 mb-4 mr-1"
                  color="primary"
                  @click="save()"
                  :disabled="disabled"
                >
                  save
                </v-btn>
                <v-btn
                  class="mb-4"
                  color="#E0E0E0"
                  @click="$router.push('/programmer_reports')"
                >
                  cancel
                </v-btn>
              </v-col>
              <v-col>
                <v-btn
                  class="mb-4 white--text float-right mr-4"
                  color="red"
                  @click="deleteOverview()"
                  v-if="permissions.project_acceptance_overview_delete"
                >
                  delete
                </v-btn>
              </v-col>
            </v-row>
          </v-card-actions>
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
      overview: { required },
    },
  },
  data() {
    return {
      absolute: true,
      overlay: false,
      input_date: false,
      holiday_date: "",
      switch1: true,
      search: "",
      headers: [
        { text: "Holiday", value: "name" },
        { text: "Date", value: "holiday_date" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      disabled: false,
      editedIndex: -1,
      editedItem: {
        project_id: "",
        intended_users: "",
        location1: "",
        location2: "",
        overview: "",
        validator_note: "",
        manager_note: "",
      },
      defaultItem: {
        project_id: "",
        intended_users: "",
        location1: "",
        location2: "",
        overview: "",
        validator_note: "",
        manager_note: "",
      },
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
          text: "Project Acceptance Overview",
          disabled: false,
        },
      ],
      permissions: Home.data().permissions,
      overviewHasRecord: false,
      user_permissions: [],
      user_roles: [],
    };
  },

  methods: {
    getAcceptanceOverview() {
      let project_id = this.$route.params.project_id;

      Axios.get("/api/acceptance_overview/index/" + project_id).then(
        (response) => {
          let project = response.data.project;

          // if project is null or accepted date is null then redirect user to unauthorize page
          if (!project || !project.accepted_date) {
            this.$router.push({ name: "unauthorize" });
          }

          if (response.data.acceptance_overview) {
            this.editedItem = response.data.acceptance_overview;
            this.overviewHasRecord = true;
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

    save() {
      let project_id = this.$route.params.project_id;
      this.editedItem.project_id = project_id;
      const data = this.editedItem;

      this.$v.$touch();

      if (!this.$v.editedItem.$error) {
        this.overlay = true;
        this.disabled = true;

        Axios.post("/api/acceptance_overview/create", data).then(
          (response) => {
            // console.log(response);
            if (response.data.success) {
              // send data to Socket.IO Server
              this.$socket.emit("sendData", {
                action: "project-acceptance-overview",
              });

              this.showAlert();
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
    },

    deleteOverview() {
      this.$swal({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Delete record!",
      }).then((result) => {
        let project_id = this.$route.params.project_id;

        if (result.value) {
          Axios.post("/api/acceptance_overview/delete", {
            project_id: project_id,
          }).then(
            (response) => {
              console.log(response.data);
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", {
                  action: "project-acceptance-overview-delete",
                });

                this.$swal({
                  position: "center",
                  icon: "success",
                  title: "Record has been deleted",
                  showConfirmButton: false,
                  timer: 2500,
                });

                this.overviewHasRecord = false;
              }
            },
            (error) => {
              this.isUnauthorized(error);
            }
          );
        }
      });
    },

    printPreview() {
      window.open(
        location.origin +
          "/acceptance_preview/" +
          this.$route.params.project_id,
        "_blank"
      );
    },
    clear() {
      this.$v.$reset();
      this.holiday_date = "";
    },
    formatDate(date) {
      if (!date) return null;

      const [year, month, day] = date.split("-");
      return `${month}/${day}/${year}`;
    },
    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },
    userRolesPermissions() {
      Axios.get("api/user/roles_permissions").then((response) => {
        this.user_permissions = response.data.user_permissions;
        this.user_roles = response.data.user_roles;
        this.getRolesPermissions();
      });
    },

    getRolesPermissions() {
      this.permissions.project_acceptance_overview = this.hasPermission([
        "project-acceptance-overview",
      ]);
      this.permissions.project_acceptance_overview_delete = this.hasPermission([
        "project-acceptance-overview-delete",
      ]);

      // if user is not authorize
      if (!this.permissions.project_acceptance_overview) {
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

        if (action == "overview-delete") {
          this.$router.push({ name: "programmer_reports" });
        }
      };
    },
  },
  computed: {
    overviewErrors() {
      const errors = [];
      if (!this.$v.editedItem.overview.$dirty) return errors;
      !this.$v.editedItem.overview.required &&
        errors.push("Overview is required.");
      return errors;
    },
  },
  mounted() {
    Axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");

    this.getAcceptanceOverview();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>