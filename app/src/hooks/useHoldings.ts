import {useQuery} from '@tanstack/react-query';
import {fetchHoldings} from '../api/portfolio';
import {useSession} from './useSession';

export const useHoldings = () => {
  const {token, isAuthenticated, hydrated} = useSession();

  return useQuery({
    queryKey: ['holdings', token],
    queryFn: () => fetchHoldings(token),
    enabled: hydrated && isAuthenticated && !!token,
    placeholderData: (prev) => prev,
  });
};
