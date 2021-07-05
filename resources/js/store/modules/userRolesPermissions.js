import axios from 'axios';
import router from '../../router';

const state = {
  userPermissions: {
    project_list: false,
    project_create: false,
    project_edit: false,
    project_delete: false,
    programmer_projects: false,
    project_log_list: false,
    project_log_create: false,
    project_log_edit: false,
    project_log_delete: false,
    user_list: false,
    user_create: false,
    user_edit: false,
    user_delete: false,
    department_list: false,
    department_create: false,
    department_edit: false,
    department_delete: false,
    manager_list: false,
    manager_create: false,
    manager_edit: false,
    manager_delete: false,
    holiday_list: false,
    holiday_create: false,
    holiday_edit: false,
    holiday_delete: false,
    role_list: false,
    role_create: false,
    role_edit: false,
    role_delete: false,
    permission_list: false,
    permission_create: false,
    permission_edit: false,
    permission_delete: false,
    ref_no_setting: false,
    print_preview: false,
    import_project: false,
    export_project: false,
    import_project_log: false,
    export_project_log: false,
    view_all_projects: false,
    edit_template_percentage: false,
    edit_program_percentage: false,
    edit_validate_percentage: false,
    endorse_project: false,
    endorse_history: false,
    project_acceptance_overview: false,
    project_acceptance_overview_delete: false,
    activity_logs: false,
  },
  userRoles: {
    administrator: false,
  },
};

const getters = {};

const actions = {
  async userRolesPermissions({ commit }) {
    let response = await axios.get("/api/user/roles_permissions").then((response) => {

      commit('setUserRoles', response.data.user_roles);
      commit('setUserPermissions', response.data.user_permissions);

    },
      (error) => {
        if (error.response.status == "401") {
          router.push({ name: "unauthorize" });
        }
      });

  },

};

const mutations = {
  setUserRoles(state, roles) {
    state.userRoles.administrator = roles.includes("Administrator");
  },
  setUserPermissions(state, permissions) {
    state.userPermissions.project_list = permissions.includes("project-list");
    state.userPermissions.project_create = permissions.includes("project-create");
    state.userPermissions.project_edit = permissions.includes("project-edit");
    state.userPermissions.project_delete = permissions.includes("project-delete");
    state.userPermissions.programmer_projects = permissions.includes(
      "programmer-projects");
    state.userPermissions.project_log_list = permissions.includes(
      "project-log-list");
    state.userPermissions.project_log_create = permissions.includes(
      "project-log-create");
    state.userPermissions.project_log_edit = permissions.includes(
      "project-log-edit");
    state.userPermissions.project_log_delete = permissions.includes(
      "project-log-delete");
    state.userPermissions.user_list = permissions.includes("user-list");
    state.userPermissions.user_create = permissions.includes("user-create");
    state.userPermissions.user_edit = permissions.includes("user-edit");
    state.userPermissions.user_delete = permissions.includes("user-delete");
    state.userPermissions.department_list = permissions.includes(
      "department-list");
    state.userPermissions.department_create = permissions.includes(
      "department-create");
    state.userPermissions.department_edit = permissions.includes(
      "department-edit");
    state.userPermissions.department_delete = permissions.includes(
      "department-delete");
    state.userPermissions.manager_list = permissions.includes("manager-list");
    state.userPermissions.manager_create = permissions.includes("manager-create");
    state.userPermissions.manager_edit = permissions.includes("manager-edit");
    state.userPermissions.manager_delete = permissions.includes("manager-delete");
    state.userPermissions.holiday_list = permissions.includes("holiday-list");
    state.userPermissions.holiday_create = permissions.includes("holiday-create");
    state.userPermissions.holiday_edit = permissions.includes("holiday-edit");
    state.userPermissions.holiday_delete = permissions.includes("holiday-delete");
    state.userPermissions.permission_list = permissions.includes(
      "permission-list");
    state.userPermissions.permission_create = permissions.includes(
      "permission-create");
    state.userPermissions.permission_edit = permissions.includes(
      "permission-edit");
    state.userPermissions.permission_delete = permissions.includes(
      "permission-delete");
    state.userPermissions.role_list = permissions.includes("role-list");
    state.userPermissions.role_create = permissions.includes("role-create");
    state.userPermissions.role_edit = permissions.includes("role-edit");
    state.userPermissions.role_delete = permissions.includes("role-delete");
    state.userPermissions.ref_no_setting = permissions.includes("ref-no-setting");
    state.userPermissions.print_preview = permissions.includes("print-preview");
    state.userPermissions.import_project = permissions.includes("import-project");
    state.userPermissions.export_project = permissions.includes("export-project");
    state.userPermissions.import_project_log = permissions.includes(
      "import-project-log");
    state.userPermissions.export_project_log = permissions.includes(
      "export-project-log");
    state.userPermissions.view_all_projects = permissions.includes(
      "view-all-projects");
    state.userPermissions.edit_template_percentage = permissions.includes(
      "edit-template-percentage");
    state.userPermissions.edit_program_percentage = permissions.includes(
      "edit-program-percentage");
    state.userPermissions.edit_validate_percentage = permissions.includes(
      "edit-validate-percentage");
    state.userPermissions.endorse_project = permissions.includes(
      "endorse-project");
    state.userPermissions.endorse_history = permissions.includes(
      "endorse-history");
    state.userPermissions.project_acceptance_overview = permissions.includes(
      "project-acceptance-overview");
    state.userPermissions.project_acceptance_overview_delete = permissions.includes(
      "project-acceptance-overview-delete");
    state.userPermissions.activity_logs = permissions.includes(
      "activity-logs");
  },

};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
}