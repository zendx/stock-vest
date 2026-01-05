import {apiRequest} from './client';
import {UserProfile} from '../types';

export const fetchUserProfile = (token?: string) =>
  apiRequest<UserProfile>('/profile', {
    method: 'GET',
    token,
  });

export const updateUserProfile = (payload: Partial<UserProfile>, token?: string) => {
  const normalized = {
    ...payload,
    smart_farming: payload.smartFarming === true ? 'yes' : payload.smartFarming === false ? 'no' : payload.smartFarming,
  };
  return apiRequest<{success: boolean; profile: UserProfile}>('/profile', {
    method: 'POST',
    token,
    body: JSON.stringify(normalized),
  });
};
