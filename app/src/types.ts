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
};

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
