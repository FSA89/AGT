// api/usersService.js
import { apiRequest } from "./apiClient.js";

export function fetchUsers() {
  return apiRequest("/api/v1/users");
}
