import axios from 'axios';
import router from '../../router';

const state = {
  user_name: "",
  user_type: "",
  user_id: ""
};

const getters = {};

const actions = {
  getUser({ commit }) {
    axios.get("/api/auth/init").then(
      (response) => {
        commit('setUser', response.data.user);
      },
      (error) => {
        // if unauthenticated (401)
        if (error.response.status == "401") {
          localStorage.removeItem("access_token");
          router.push('/login');
        }
      }
    );
  },
};

const mutations = {
  setUser(state, data) {
    state.user_name = data.name;
    state.user_type = data.type;
    state.user_id = data.id;
  }
};

export default {  
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}