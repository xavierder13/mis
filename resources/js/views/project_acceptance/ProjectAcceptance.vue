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
          <v-card-text class="ml-2">
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
                  class="ml-4 mb-4 mr-1"
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
      dialog: false,
      holidays: [],
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
      loading: true,
      overviewHasRecord: false,
    };
  },

  methods: {
    getAcceptanceOverview() {
      let project_id = this.$route.params.project_id;

      Axios.get("/api/acceptance_overview/index/" + project_id, {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          console.log(response.data.acceptance_overview);
          if (response.data.acceptance_overview) {
            this.editedItem = response.data.acceptance_overview;
            this.overviewHasRecord = true;
          }
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

        Axios.post("/api/acceptance_overview/create", data, {
          headers: {
            Authorization: "Bearer " + access_token,
          },
        }).then(
          (response) => {
            // console.log(response);
            if (response.data.success) {
              this.showAlert();
            }
            this.overlay = false;
            this.disabled = false;
          },
          (errors) => {
            console.log(errors);
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
          Axios.post(
            "/api/acceptance_overview/delete",
            { project_id: project_id },
            {
              headers: {
                Authorization: "Bearer " + access_token,
              },
            }
          ).then(
            (response) => {
              console.log(response.data);
              if (response.data.success) {
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
              // if unauthenticated (401)
              if (error.response.status == "401") {
                localStorage.removeItem("access_token");
                this.$router.push({ name: "login" });
              }
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
      this.permissions.holiday_list = Home.methods.hasPermission([
        "holiday-list",
      ]);
      this.permissions.holiday_create = Home.methods.hasPermission([
        "holiday-create",
      ]);
      this.permissions.holiday_edit = Home.methods.hasPermission([
        "holiday-edit",
      ]);
      this.permissions.holiday_delete = Home.methods.hasPermission([
        "holiday-delete",
      ]);

      // hide column actions if user has no permission
      if (!this.permissions.holiday_edit && !this.permissions.holiday_delete) {
        this.headers[2].align = " d-none";
      } else {
        this.headers[2].align = "";
      }

      // if user is not authorize
      if (!this.permissions.holiday_list && !this.permissions.holiday_create) {
        this.$router.push("/unauthorize").catch(() => {});
      }
    },
    websocket() {

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
    access_token = localStorage.getItem("access_token");

    this.getAcceptanceOverview();
    this.userRolesPermissions();
    this.websocket();

  },
};
</script>