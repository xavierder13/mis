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
            User List
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
                  @click="clear() + (dialog = true)"
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
                              name="name"
                              v-model="editedItem.name"
                              :error-messages="nameErrors"
                              label="Full Name"
                              @input="$v.editedItem.name.$touch()"
                              @blur="$v.editedItem.name.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="pa-0 ma-0">
                            <v-text-field
                              name="email"
                              v-model="editedItem.email"
                              :error-messages="emailErrors"
                              label="E-mail"
                              @input="$v.editedItem.email.$touch()"
                              @blur="$v.editedItem.email.$touch()"
                              :readonly="emailReadonly"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="pa-0 ma-0">
                            <v-text-field
                              name="password"
                              v-model="password"
                              :error-messages="passwordErrors"
                              label="Password"
                              required
                              @input="$v.password.$touch()"
                              @blur="$v.password.$touch() + dummyPassword"
                              @keyup="passwordChange()"
                              @focus="onFocus()"
                              type="password"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="pa-0 ma-0">
                            <v-text-field
                              name="confirm_password"
                              v-model="confirm_password"
                              :error-messages="confirm_passwordErrors"
                              label="Confirm Password"
                              required
                              @input="$v.confirm_password.$touch()"
                              @blur="$v.confirm_password.$touch() + dummyPassword"
                              @keyup="passwordChange()"
                              @focus="onFocus()"
                              type="password"
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
                              @change="
                                $v.editedItem.type.$touch()
                              "
                              @blur="$v.editedItem.type.$touch()"
                            ></v-autocomplete>
                          </v-col>
                        </v-row>
                        <!-- <v-row>
                          <v-col>
                            <v-combobox
                              v-model="role"
                              :items="roles"
                              item-text="name"
                              item-value="id"
                              label="Roles"
                              multiple
                              chips
                            ></v-combobox>
                          </v-col>
                        </v-row> -->
                        <v-row>
                          <v-col cols="2" class="pa-0 ma-0">
                            <v-switch
                              v-model="switch1"
                              :label="activeStatus"
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
                        @click="save"
                        :disabled="disabled"
                        class="mb-4"
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
            :items="users"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon small class="mr-2" color="green" @click="editUser(item)">
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
import {
  required,
  maxLength,
  email,
  minLength,
  sameAs,
} from "vuelidate/lib/validators";

export default {
  mixins: [validationMixin],

  validations: {
    editedItem: {
      name: { required },
      email: { required, email },
      type: { required },
    },
    password: { required, minLength: minLength(8) },
    confirm_password: { required, sameAsPassword: sameAs("password") },
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
          text: "Users Record",
          disabled: true,
        },
      ],
      search: "",
      headers: [
        { text: "Full Name", value: "name" },
        { text: "E-mail", value: "email" },
        { text: "Active", value: "active" },
        { text: "User Type", value: "type" },
        { text: "Last Login", value: "last_login" },
        { text: "Actions", value: "actions", sortable: false },
      ],
      switch1: true,
      disabled: false,
      emailReadonly: false,
      dialog: false,
      users: [],
      types: [
        { text: "Programmer", value: "Programmer" },
        { text: "Validator", value: "Validator" },
      ],
      editedIndex: -1,
      editedItem: {
        name: "",
        email: "",
        type: "",
        role: "",
        roles: [],
        active: "",
      },
      defaultItem: {
        name: "",
        email: "",
        password: "",
        confirm_password: "",
        type:"",
        role: "",
        roles: [],
        active: "",
      },
      password: "",
      confirm_password: "",
      permissions: {
        user_list: false,
        user_create: false,
        user_edit: false,
        user_delete: false,
      },
      loading: true,
      passwordHasChanged: false,
    };
  },

  methods: {
    getUser() {
      this.loading = true;
      Axios.get("/api/user/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then((response) => {
        this.users = response.data.users;
        this.loading = false;
      });
    },

    editUser(item) {
      this.editedIndex = this.users.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.dialog = true;
      this.emailReadonly = true;
      this.password = "password";
      this.confirm_password = "password";
    },

    deleteUser(user_id) {
      const data = { user_id: user_id };

      Axios.post("/api/user/delete", data, {
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

          const user_id = item.id;
          const index = this.users.indexOf(item);

          //Call delete User function
          this.deleteProject(user_id);

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
          
          if(this.passwordHasChanged)
          {
            this.editedItem.password = this.password;
            this.editedItem.confirm_password = this.confirm_password;
          }
          
          const data = this.editedItem;
          const user_id = this.editedItem.id;

          Axios.post("/api/user/update/" + user_id, data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              console.log(response.data);
              if (response.data.success) {
                Object.assign(this.users[this.editedIndex], this.editedItem);
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
          
          this.editedItem.password = this.password;
          this.editedItem.confirm_password = this.confirm_password;

          const data = this.editedItem;

          Axios.post("/api/user/store", data, {
            headers: {
              Authorization: "Bearer " + access_token,
            },
          }).then(
            (response) => {
              if (response.data.success) {
                this.disabled = false;
                this.showAlert();
                this.close();

                //push recently added data from database
                this.users.push(response.data.user);
              }
            },
            (error) => {
              console.log(error);
            }
          );
        }
      }
    },
    clear() {
      this.$v.$reset();
      this.editedItem.email = "";
      this.emailReadonly = false;
      this.password = "";
      this.confirm_password = "";
      this.editedItem.active = true;
      this.passwordHasChanged = false;
      this.switch1 = true;
    },
    onFocus() {

      if (this.editedIndex > -1) {
        if (!this.passwordHasChanged) {
          this.password = "";
          this.confirm_password = "";
        }
      }
    },
    passwordChange() {
      if(this.password || this.confirm_password)
      {
        this.passwordHasChanged = true;
      }
      else
      {
        this.passwordHasChanged = false;
      }
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New User" : "Edit User";
    },
    nameErrors() {
      const errors = [];
      if (!this.$v.editedItem.name.$dirty) return errors;
      !this.$v.editedItem.name.required && errors.push("Name is required.");
      return errors;
    },
    emailErrors() {
      const errors = [];
      if (!this.$v.editedItem.email.$dirty) return errors;
      !this.$v.editedItem.email.required && errors.push("Email is required.");
      !this.$v.editedItem.email.email && errors.push("Must be valid e-mail");
      return errors;
    },
    passwordErrors() {
      const errors = [];
      if (!this.$v.password.$dirty) return errors;
      !this.$v.password.required && errors.push("Password is required.");
      !this.$v.password.minLength &&
        errors.push("Password must be atleast 8 characters.");
      return errors;
    },

    confirm_passwordErrors() {
      const errors = [];
      if (!this.$v.confirm_password.$dirty) return errors;
      !this.$v.confirm_password.required &&
        errors.push("Password Confirmation is required.");
      !this.$v.confirm_password.sameAsPassword &&
        errors.push("Passwords did not match");
      return errors;
    },

    typeErrors() {
      const errors = [];
      if (!this.$v.editedItem.type.$dirty) return errors;
      !this.$v.editedItem.type.required &&
        errors.push("User type is required.");
      return errors;
    },
    activeStatus() {
      if (this.switch1) {
        this.editedItem.active = "Y";
        return " Active";
      } else {
        this.editedItem.active = "N";
        return " Inactive";
      }
    },
    dummyPassword() {
      if (this.editedIndex > -1) {
        if (!this.password && !this.confirm_password) {
          this.password = "password";
          this.confirm_password = "password";
        }
      }
    },
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getUser();
  },
};
</script>