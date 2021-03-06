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
            Holiday Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              v-if="userPermissions.holiday_list"
            ></v-text-field>
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>

                <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="clear() + (dialog = true)"
                  v-if="userPermissions.holiday_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>

                <v-dialog v-model="dialog" max-width="500px" persistent>
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="holiday"
                              v-model="editedItem.holiday"
                              label="Holiday"
                              required
                              :error-messages="holidayErrors"
                              @input="$v.editedItem.holiday.$touch()"
                              @blur="$v.editedItem.holiday.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-menu
                              v-model="input_date"
                              :close-on-content-click="false"
                              transition="scale-transition"
                              offset-y
                              max-width="290px"
                              min-width="290px"
                            >
                              <template v-slot:activator="{ on, attrs }">
                                <v-text-field
                                  name="holiday_date"
                                  v-model="computedHolidayDateFormatted"
                                  label="Holiday Date"
                                  hint="MM/DD/YYYY format"
                                  persistent-hint
                                  prepend-icon="mdi-calendar"
                                  readonly
                                  v-bind="attrs"
                                  v-on="on"
                                  :error-messages="dateErrors"
                                  @input="$v.editedItem.holiday_date.$touch()"
                                  @blur="$v.editedItem.holiday_date.$touch()"
                                ></v-text-field>
                              </template>
                              <v-date-picker
                                v-model="holiday_date"
                                no-title
                                @input="input_date = false"
                              ></v-date-picker>
                            </v-menu>
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
            :items="holidays"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="userPermissions.holiday_list"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editHoliday(item)"
                v-if="userPermissions.holiday_edit"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="userPermissions.holiday_delete"
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
import axios from "axios";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";
import { mapState } from "vuex";

export default {
  mixins: [validationMixin],

  validations: {
    editedItem: {
      holiday: { required },
      holiday_date: { required },
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
        holiday: "",
        holiday_date: "",
      },
      defaultItem: {
        holiday: "",
        holiday_date: "",
      },
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/dashboard",
        },
        {
          text: "Holiday Lists",
          disabled: true,
        },
      ],
      loading: true,
    };
  },

  methods: {
    getHoliday() {
      this.loading = true;
      axios.get("/api/holiday/index").then(
        (response) => {
          this.holidays = response.data.holidays;
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editHoliday(item) {
      let holiday_date = "";

      this.editedIndex = this.holidays.indexOf(item);
      this.editedItem.id = item.id;
      this.editedItem.holiday = item.name;
      this.holiday_date = item.holiday_date;
      this.dialog = true;

      if (item.holiday_date) {
        holiday_date = item.holiday_date.split("/");
        this.holiday_date =
          holiday_date[2] + "-" + holiday_date[0] + "-" + holiday_date[1];
      }
    },

    deleteHoliday(holiday_id) {
      const data = { holiday_id: holiday_id };

      axios.post("/api/holiday/delete", data).then(
        (response) => {
          // console.log(response.data);
          if (response.data.success) {
            // send data to Socket.IO Server
            this.$socket.emit("sendData", { action: "holiday-delete" });
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

          const holiday_id = item.id;
          const index = this.holidays.indexOf(item);

          //Call delete Holiday function
          this.deleteHoliday(holiday_id);

          //Remove item from array services
          this.holidays.splice(index, 1);

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
        this.overlay = true;
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedItem;
          const holiday_id = this.editedItem.id;

          axios.post("/api/holiday/update/" + holiday_id, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "holiday-update" });

                Object.assign(
                  this.holidays[this.editedIndex],
                  response.data.holiday
                );
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

          axios.post("/api/holiday/store", data).then(
            (response) => {
              if (response.data.success) {
                // send data to Socket.IO Server
                this.$socket.emit("sendData", { action: "holiday-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.holidays.push(response.data.holiday);
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

    websocket() {
      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;

        if (
          action == "holiday-create" ||
          action == "holiday-edit" ||
          action == "holiday-delete"
        ) {
          this.getHoliday();
        }
      };
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Holiday" : "Edit Holiday";
    },
    holidayErrors() {
      const errors = [];
      if (!this.$v.editedItem.holiday.$dirty) return errors;
      !this.$v.editedItem.holiday.required &&
        errors.push("Holiday is required.");
      return errors;
    },
    dateErrors() {
      const errors = [];
      if (!this.$v.editedItem.holiday_date.$dirty) return errors;
      !this.$v.editedItem.holiday_date.required &&
        errors.push("Date is required.");
      return errors;
    },
    computedHolidayDateFormatted() {
      this.editedItem.holiday_date = this.formatDate(this.holiday_date);
      return this.editedItem.holiday_date;
    },
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");

    this.getHoliday();
    this.websocket();
  },
};
</script>