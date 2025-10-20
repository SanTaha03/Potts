import axios from "axios";
export const api = axios.create({
  baseURL: "/api",
  withCredentials: true,
});

 async function initCrsf(){
  await api.get('/sanctum/csrf-token');
}
async function login (email : string, password : string) {
  await initCrsf();
  const data  = await api.post('api/login', {email, password});
  return data;
}

async function getDevices(page = 1, q = '') {
  const { data } = await api.get("/api/devices");
  return data;
}
export {
  login,
  getDevices,
  initCrsf
}

