import {useQuery} from '@tanstack/react-query';
import {fetchStocks} from '../api/portfolio';
import {useSession} from './useSession';

export const useStocks = () => {
  const {token, isAuthenticated, hydrated} = useSession();

  return useQuery({
    queryKey: ['stocks', token],
    queryFn: () => fetchStocks(token),
    enabled: hydrated && isAuthenticated && !!token,
    placeholderData: (prev) => prev,
  });
};
