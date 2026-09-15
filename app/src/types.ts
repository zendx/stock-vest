export type User = {
  id: string;
  name: string;
  email: string;
  phone?: string;
};

export type Balances = {
  totalAssets: number;
  profit: number;
  available: number;
  net: number;
<<<<<<< HEAD
  totalAssetsLocked: boolean;
  totalAssetsUnlockedAmount: number;
  totalAssetsLockedAmount: number;
  totalAssetsWithdrawable: boolean;
  totalAssetsUnlockAt: string | null;
};

export type WithdrawalSource = 'available_balance' | 'total_assets';
export type WithdrawalRequest = {amount: string; destination: string; withdrawal_source: WithdrawalSource; note?: string};

=======
};

>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
export type Stock = {
  id: string;
  name: string;
  rate: string;
  status: string;
  price: string;
};

export type TransactionStatus = 'approved' | 'processing' | 'pending' | 'failed';

export type Transaction = {
  id: string;
  type?: string;
  title: string;
  amount: string;
  date: string;
  status: TransactionStatus;
  note?: string;
};

export type Holding = {
  id: string;
  stockId: string;
  name: string;
  invested: string;
  shares: string;
  profit: string;
  rate: string;
  status: string;
  createdAt: string;
  currentPrice?: string | null;
  image?: string | null;
};

export type UserProfile = {
  email: string;
  name: string;
  firstName?: string;
  lastName?: string;
  phone?: string;
  birthDate?: string;
  address1?: string;
  address2?: string;
  landmark?: string;
  street?: string;
  country?: string;
  state?: string;
  city?: string;
  zip?: string;
  smartFarming?: boolean;
};
