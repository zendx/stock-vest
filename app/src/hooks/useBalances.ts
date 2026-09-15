import {useQuery} from '@tanstack/react-query';
import {fetchBalances} from '../api/portfolio';
import {useSession} from './useSession';

export const useBalances = () => {
  const {token, isAuthenticated, hydrated} = useSession();

  return useQuery({
    queryKey: ['balances', token],
    queryFn: () => fetchBalances(token),
    enabled: hydrated && isAuthenticated && !!token,
    placeholderData: (prev) => prev,
<<<<<<< HEAD
    refetchInterval: 30000,
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
  });
};
