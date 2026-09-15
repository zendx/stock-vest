import Constants from 'expo-constants';
import {apiRequest, ApiError} from './client';
import {Balances, Stock, Transaction, Holding, WithdrawalRequest} from '../types';

const nativeWritesEnabled =
  process.env.EXPO_PUBLIC_ENABLE_NATIVE_WRITES === 'true' ||
  Constants.expoConfig?.extra?.nativeWritesEnabled === true;

export class NativeWriteUnavailableError extends ApiError {
  constructor() {
    super('This transaction is currently completed on the secure COFCO Capital website.');
    this.name = 'NativeWriteUnavailableError';
  }
}

const postNative = <T>(path: string, payload: Record<string, unknown>, token?: string) => {
  if (!nativeWritesEnabled) throw new NativeWriteUnavailableError();
  if (!token) throw new ApiError('Authentication required.', 401);

  return apiRequest<T>(path, {
    method: 'POST',
    token,
    body: JSON.stringify(payload),
  });
};

export const fetchBalances = (token?: string) =>
  apiRequest<Balances>('/balances', {
    method: 'GET',
    token,
  });

export const fetchStocks = (token?: string) =>
  apiRequest<Stock[]>('/stocks', {
    method: 'GET',
    token,
  });

export const fetchTransactions = (token?: string) =>
  apiRequest<Transaction[]>('/transactions', {
    method: 'GET',
    token,
  });

export const fetchHoldings = (token?: string) =>
  apiRequest<Holding[]>('/holdings', {
    method: 'GET',
    token,
  });

export const submitDeposit = (payload: {amount: string; method: string; note?: string}, token?: string) =>
  postNative<{success: boolean; message?: string}>('/deposits', payload, token);

export const submitWithdrawal = (payload: WithdrawalRequest, token?: string) =>
  postNative<{success: boolean; message?: string}>('/withdrawals', payload, token);

export const submitReinvest = (payload: {amount: string; note?: string}, token?: string) =>
  postNative<{success: boolean; message?: string}>('/reinvestments', payload, token);

export const submitBuyStock = (payload: {stockId: string; units?: string; amount?: string}, token?: string) =>
  postNative<{success: boolean; message?: string}>('/orders', payload, token);
