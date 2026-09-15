import {apiRequest} from './client';
import {UserProfile} from '../types';

export const fetchUserProfile = (token?: string) =>
  apiRequest<UserProfile>('/profile', {
    method: 'GET',
    token,
  });

export const updateUserProfile = (payload: Partial<UserProfile>, token?: string) => {
  const normalized = Object.keys(payload).reduce<Record<string, unknown>>((result, key) => {
    const value = payload[key as keyof UserProfile];
    const snakeCaseKey = key.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
    result[snakeCaseKey] =
      snakeCaseKey === 'smart_farming' && typeof value === 'boolean' ? (value ? 'yes' : 'no') : value;
    return result;
  }, {});

  return apiRequest<{success: boolean; profile: UserProfile}>('/profile', {
    method: 'POST',
    token,
    body: JSON.stringify(normalized),
  });
};
