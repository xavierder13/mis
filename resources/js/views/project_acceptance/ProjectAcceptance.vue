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
          <v-card-title> Project Acceptance </v-card-title>
          <v-card-text>
            <v-row>
              <v-col>
                <ckeditor
                  v-model="editorData"
                  :config="editorConfig"
                ></ckeditor>
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions>
            <v-btn
              class="ml-2 mb-2"
              color="primary"
              @click="save()"
              :disabled="disabled"
            >
              save
            </v-btn>
            <v-btn class="mb-2" color="primary" @click="printPreview()">
              preview
            </v-btn>
            <v-btn
              class="mb-2"
              color="#E0E0E0"
              @click="$router.push('/programmer_reports')"
            >
              cancel
            </v-btn>
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
      permissions: Home.data().permissions,
      loading: true,
      editorData: "",
      editorConfig: {
        toolbar: [
          {
            name: "clipboard",
            items: ["Undo", "Redo"],
          },
          {
            name: "basicstyles",
            items: [
              "Bold",
              "Italic",
              "Underline",
              "Strike",
              "RemoveFormat",
              "Subscript",
              "Superscript",
            ],
          },
          {
            name: "paragraph",
            items: [
              "NumberedList",
              "BulletedList",
              "-",
              "Outdent",
              "Indent",
              "-",
              "Blockquote",
            ],
          },
          {
            name: "insert",
            items: ["Table"],
          },
          "/",

          {
            name: "styles",
            items: ["Format", "Font", "FontSize"],
          },
          {
            name: "colors",
            items: ["TextColor", "BGColor", "CopyFormatting"],
          },
          {
            name: "align",
            items: [
              "JustifyLeft",
              "JustifyCenter",
              "JustifyRight",
              "JustifyBlock",
            ],
          },
          {
            name: "document",
            items: ["PageBreak"],
          },
        ],

        // Enabling extra plugins, available in the full-all preset: https://ckeditor.com/cke4/presets
        extraPlugins:
          "colordialog,copyformatting,colorbutton,font,justify,print,tableresize,uploadimage,pastefromword,liststyle,pagebreak,autogrow",
        // Make the editing area bigger than default.

        autoGrow_minHeight: 500,
        autoGrow_maxHeight: 600,
        autoGrow_bottomSpace: 50,
        removePlugins: "resize",

        // An array of stylesheets to style the WYSIWYG area.
        // Note: it is recommended to keep your own styles in a separate file in order to make future updates painless.
        contentsCss: [
          "http://cdn.ckeditor.com/4.15.1/full-all/contents.css",
          "https://ckeditor.com/docs/ckeditor4/4.15.1/examples/assets/css/pastefromword.css",
        ],

        // This is optional, but will let us define multiple different styles for multiple editors using the same CSS file.
        bodyClass: "document-editor",

        // Reduce the list of block elements listed in the Format dropdown to the most commonly used.
        //format_tags: 'p;h1;h2;h3;pre',

        // Simplify the Image and Link dialog windows. The "Advanced" tab is not needed in most cases.
        removeDialogTabs: "image:advanced;link:advanced",

        // Define the list of styles which should be available in the Styles dropdown list.
        // If the "class" attribute is used to style an element, make sure to define the style for the class in "mystyles.css"
        // (and on your website so that it rendered in the same way).
        // Note: by default CKEditor looks for styles.js file. Defining stylesSet inline (as below) stops CKEditor 4 from loading
        // that file, which means one HTTP request less (and a faster startup).
        // For more information see https://ckeditor.com/docs/ckeditor4/latest/features/styles
        stylesSet: [
          /* Inline Styles */
          {
            name: "Marker",
            element: "span",
            attributes: {
              class: "marker",
            },
          },
          {
            name: "Cited Work",
            element: "cite",
          },
          {
            name: "Inline Quotation",
            element: "q",
          },

          /* Object Styles */
          {
            name: "Special Container",
            element: "div",
            styles: {
              padding: "5px 10px",
              background: "#eee",
              border: "1px solid #ccc",
            },
          },
          {
            name: "Compact table",
            element: "table",
            attributes: {
              cellpadding: "5",
              cellspacing: "0",
              border: "1",
              bordercolor: "#ccc",
            },
            styles: {
              "border-collapse": "collapse",
            },
          },
          {
            name: "Borderless Table",
            element: "table",
            styles: {
              "border-style": "hidden",
              "background-color": "#E6E6FA",
            },
          },
          {
            name: "Square Bulleted List",
            element: "ul",
            styles: {
              "list-style-type": "square",
            },
          },
          {
            name: "RightInd-nil",
            element: "div",
            styles: { "margin-right": "" },
          },
          {
            name: "RightInd-00",
            element: "div",
            styles: { "margin-right": "0px" },
          },
          {
            name: "RightInd-20",
            element: "div",
            styles: { "margin-right": "20px" },
          },
          {
            name: "RightInd-40",
            element: "div",
            styles: { "margin-right": "40px" },
          },
          {
            name: "RightInd-60",
            element: "div",
            styles: { "margin-right": "60px" },
          },
        ],
      },
    };
  },

  methods: {
    getTemplate() {
      this.loading = true;
      Axios.get("/api/holiday/index", {
        headers: {
          Authorization: "Bearer " + access_token,
        },
      }).then(
        (response) => {
          this.holidays = response.data.holidays;
          this.loading = false;
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

    save() {},

    printPreview() {
      Axios.post(
        "/api/project/project_acceptance",
        { data: this.editorData },
        {
          headers: {
            Authorization: "Bearer " + access_token,
          },
        }
      ).then(
        (response) => {
          

          var myWindow = window.open("", "", "width=600,height=800"); 

          myWindow.document.write(response.data);
          
          console.log(response.data);

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
      // window.Echo.channel("WebsocketChannel").listen("WebsocketEvent", (e) => {
      //   let action = e.data.action;

      //   if (
      //     action == "user-edit" ||
      //     action == "role-edit" ||
      //     action == "role-delete" ||
      //     action == "permission-delete"
      //   ) {

      //     this.userRolesPermissions();
      //   }

      //   if(action == 'holiday-create' || action == 'holiday-edit' || action == 'holiday-delete')
      //   {
      //     this.getTemplate();
      //   }

      // });

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
      };
    },
  },
  computed: {},
  mounted() {
    access_token = localStorage.getItem("access_token");

    this.getTemplate();
    this.userRolesPermissions();
    this.websocket();
  },
};
</script>