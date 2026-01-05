import {useMutation, useQuery} from '@tanstack/react-query';
import {fetchUserProfile, updateUserProfile} from '../api/profile';
import {useSession} from './useSession';
import {queryClient} from '../lib/queryClient';

export const useUserProfile = () => {
  const {token, isAuthenticated, hydrated} = useSession();

  const query = useQuery({
    queryKey: ['profile', token],
    queryFn: () => fetchUserProfile(token),
    enabled: hydrated && isAuthenticated && !!token,
  });

  const mutation = useMutation({
    mutationFn: (payload: Record<string, any>) => updateUserProfile(payload, token),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['profile']});
    },
  });

  return {query, mutation};
};
