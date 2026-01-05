import {apiRequest} from './client';
import {User} from '../types';

export type AuthResponse = {
  token: string;
  user: User;
};

export const login = (payload: {identifier: string; password: string}) => {
  const {identifier, password} = payload;
  // Send multiple aliases so the backend can resolve by username or email, matching the WordPress login form.
  const body = {
    identifier,
    login: identifier,
    email: identifier,
    username: identifier,
    password,
  };

  return apiRequest<AuthResponse>('/auth/login', {
    method: 'POST',
    body: JSON.stringify(body),
  });
};

export const signup = (payload: {name: string; email: string; password: string; phone?: string; referralCode: string}) =>
  apiRequest<AuthResponse>('/auth/signup', {
    method: 'POST',
    body: JSON.stringify(payload),
  });

export const fetchProfile = (token: string) =>
  apiRequest<User>('/auth/me', {
    method: 'GET',
    token,
  });
