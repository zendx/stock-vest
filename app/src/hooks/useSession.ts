import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import create from 'zustand';
import {persist, createJSONStorage} from 'zustand/middleware';
import {login as apiLogin, signup as apiSignup, fetchProfile} from '../api/auth';
import {User} from '../types';
import {queryClient} from '../lib/queryClient';

type SessionState = {
  status: 'idle' | 'loading' | 'hydrating' | 'error';
  isAuthenticated: boolean;
  hydrated: boolean;
  token?: string;
  user?: User;
  error?: string;
  hydrate: () => Promise<void>;
  login: (payload: {identifier: string; password: string; remember?: boolean}) => Promise<void>;
  signup: (payload: {name: string; email: string; password: string; phone?: string; referralCode: string}) => Promise<void>;
  logout: () => Promise<void>;
};

const TOKEN_KEY = 'cofco-session-token';

const storeToken = async (token?: string) => {
  if (!token) {
    await SecureStore.deleteItemAsync(TOKEN_KEY).catch(() => {});
    await AsyncStorage.removeItem(TOKEN_KEY).catch(() => {});
    return;
  }
  try {
    await SecureStore.setItemAsync(TOKEN_KEY, token);
  } catch {
    await AsyncStorage.setItem(TOKEN_KEY, token);
  }
};

const loadToken = async () => {
  const secure = await SecureStore.getItemAsync(TOKEN_KEY);
  if (secure) return secure;
  return AsyncStorage.getItem(TOKEN_KEY);
};

export const useSession = create<SessionState>()(
  persist(
    (set, get) => ({
      status: 'hydrating',
      isAuthenticated: false,
      hydrated: false,
      token: undefined,
      user: undefined,
      error: undefined,
      hydrate: async () => {
        if (get().hydrated) return;
        set({status: 'hydrating'});
        const token = await loadToken();
        if (!token) {
          set({hydrated: true, status: 'idle', isAuthenticated: false, token: undefined});
          return;
        }
        try {
          const profile = await fetchProfile(token);
          set({hydrated: true, status: 'idle', isAuthenticated: true, token, user: profile, error: undefined});
        } catch (err: any) {
          set({hydrated: true, status: 'error', isAuthenticated: false, token: undefined, user: undefined, error: err?.message || 'Session expired'});
          await storeToken(undefined);
        }
      },
      login: async ({identifier, password, remember = true}) => {
        set({status: 'loading', error: undefined});
        try {
          const {token, user} = await apiLogin({identifier, password});
          await storeToken(remember ? token : undefined);
          queryClient.removeQueries();
          set({isAuthenticated: true, token, user, status: 'idle'});
        } catch (err: any) {
          set({status: 'error', isAuthenticated: false, token: undefined, user: undefined, error: err?.message || 'Login failed'});
          throw err;
        }
      },
      signup: async ({name, email, password, phone, referralCode}) => {
        set({status: 'loading', error: undefined});
        try {
          const {token, user} = await apiSignup({name, email, password, phone, referralCode});
          await storeToken(token);
          queryClient.removeQueries();
          set({isAuthenticated: true, token, user, status: 'idle'});
        } catch (err: any) {
          set({status: 'error', isAuthenticated: false, token: undefined, user: undefined, error: err?.message || 'Signup failed'});
          throw err;
        }
      },
      logout: async () => {
        await storeToken(undefined);
        queryClient.clear();
        set({isAuthenticated: false, token: undefined, user: undefined, status: 'idle', error: undefined});
      },
    }),
    {
      name: 'cofco-session',
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({
        user: state.user,
      }),
      onRehydrateStorage: () => (state) => {
        state?.hydrate().catch(() => {});
      },
    },
  ),
);
