import {createStore} from "vuex";

const store = createStore({
  state: {
    user: {
      data : {
        name: 'x',
        token: null
      }
    }
  },
  getters: {},
  actions: {},
  mutations: {},
  modules: {}
})

export default store;
