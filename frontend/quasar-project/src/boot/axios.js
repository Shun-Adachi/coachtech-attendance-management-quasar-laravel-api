import { boot } from 'quasar/wrappers';
import axios from 'axios';

//axios.defaults.withCredentials = true;
//axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
//axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE || '/api',
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
});

export default boot(({ app }) => {
  app.config.globalProperties.$api = api;
});
export { api };
