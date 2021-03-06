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
            <span class="headline">Reference No. Setting</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="ml-2">
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
          </v-card-text>
          <v-card-actions>
            <v-btn
              class="ml-2 mb-2"
              color="primary"
              @click="save"
              :disabled="disabled"
            >
              Save
            </v-btn>
            <!-- <v-btn color="#E0E0E0" @click="clear()"> Clear </v-btn> -->
          </v-card-actions>
        </v-card>
      </v-main>
    </div>
  </div>
</template>
<script>

import axios from "axios";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";
import { mapState } from 'vuex';

export default {

  mixins: [validationMixin],

  validations: {
    start: { required },
  },
  data() {
    return {
      absolute: true,
      overlay: false,
      switch1: false,
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
      start: "",
      active: false,
      settings: [],

    };
  },

  methods: {
    getSettings() {
      axios.get("/api/ref_no_setting/index").then(
        (response) => {
          this.settings = response.data.settings;

          this.start = this.settings.start;
          this.active = this.settings.active;
          if (this.settings.active == "Y") {
            this.switch1 = true;
          } else {
            this.switch1 = false;
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
      if (!this.$v.$error) {
        this.disabled = true;
        this.overlay = true;

        const data = {
          start: this.start,
          active: this.active,
        };

        let settings_id = this.settings.id;

        axios.post("/api/ref_no_setting/update/" + settings_id, data).then(
          (response) => {
   
            if (response.data.success) {
              // send data to Sockot.IO Server
              this.$socket.emit("sendData", { action: "ref_no_settings" });
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

        if (action == "project-create") {
          this.getSettings();
        }
      };
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
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getSettings();
    this.websocket();
  },
};
</script>