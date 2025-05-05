import { createContext } from "react";

export type User = {
  email: string;
};

export interface AuthContextType {
  user: User | null;
  token: string | null;
  loginUser: (email: string, password: string) => Promise<void>;
  logoutUser: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextType | null>({
  user: null,
  token: null,
  loginUser: async () => {},
  logoutUser: async () => {},
});
