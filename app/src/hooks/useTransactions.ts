import {useQuery} from '@tanstack/react-query';
import {fetchTransactions} from '../api/portfolio';
import {useSession} from './useSession';

export const useTransactions = () => {
  const {token, isAuthenticated, hydrated} = useSession();

  return useQuery({
    queryKey: ['transactions', token],
    queryFn: () => fetchTransactions(token),
    enabled: hydrated && isAuthenticated && !!token,
    placeholderData: (prev) => prev,
  });
};
