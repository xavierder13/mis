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
          <v-card-title class="grey darken-2 text-white">
            <span class="headline">Referrence No. Setting</span>
          </v-card-title>

          <v-card-text class="pa-10">
            <v-row>
              <v-col cols="4">
                <v-text-field-integer
                  name="start"
                  v-model="start"
                  label="Start"
                  v-bind:properties="{
                    placeholder: '0',
                    maxLength: 6,
                  }"
                >
                </v-text-field-integer>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4">
                <v-switch v-model="switch1" :label="activeStatus"></v-switch>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4">
                <v-btn
                  color="primary"
                  @click="save"
                  class="mr-4"
                  :disabled="disabled"
                >
                  Save
                </v-btn>
                <!-- <v-btn color="#E0E0E0" @click="clear()"> Clear </v-btn> -->
              </v-col>
            </v-row>
          </v-card-text>
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
    start: { required },
  },
  data() {
    return {
      absolute: true,
      overlay: false,
      switch1: true,
      disabled: false,
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/dashboard",
        },
        {
          text: "Ref No. Setting",
          disabled: true,
        },
      ],
      permissions: {
        ref_no_setting: false,
      },
      start: "",
      active: false,
      settings: [],
    };
  },

  methods: {
    getSettings() {
      Axios.get("/api/ref_no_setting/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then((response) => {
        console.log(response.data);
        this.settings = response.data.settings;

        this.start = this.settings.start;
        this.active = this.settings.active;
        if (this.settings.active == "Y") {
          this.switch1 = true;
        } else {
          this.switch1 = false;
        }
      }, (error) => {
        // if unauthenticated (401)
        if(error.response.status == '401')
        {
          localStorage.removeItem('access_token');
          this.$router.push({name: 'login'});
        }
      }, (error) => {
        // if unauthenticated (401)
        if(error.response.status)
        {
          localStorage.removeItem('access_token');
          this.$router.push({name: 'login'});
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

    save() {
      if (!this.$v.$error) {
        this.disabled = true;
        this.overlay = true;

        const data = {
          start: this.start,
          active: this.active,
        };

        let settings_id = this.settings.id;

        Axios.post("/api/ref_no_setting/update/" + settings_id, data, {
          headers: {
            Authorization: "Bearer " + access_token,
          },
        }).then(
          (response) => {
            console.log(response.data);
            if (response.data.success) {
              this.showAlert();
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
    clear() {
      this.$v.$reset();
      this.start = "";
      this.active = "N";
      this.switch1 = false;
    },
  },
  computed: {
    startErrors() {
      const errors = [];
      if (!this.$v.start.$dirty) return errors;
      !this.$v.start.required && errors.push("Ref No. Start is required.");
      return errors;
    },
    activeStatus() {
      if (this.switch1) {
        this.active = "Y";
        return " Active";
      } else {
        this.active = "N";
        return " Inactive";
      }
    },
  },
  mounted() {
    access_token = localStorage.getItem("access_token");
    this.getSettings();
  },
};
</script>