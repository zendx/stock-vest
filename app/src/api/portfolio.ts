import Constants from 'expo-constants';
import {apiRequest, adminAjaxUrl, adminPostUrl} from './client';
import {Balances, Stock, Transaction, Holding} from '../types';
import {ApiError} from './client';

const getEnv = (key: string) =>
  process.env[key] ||
  (Constants.expoConfig?.extra ? (Constants.expoConfig.extra as Record<string, string | undefined>)[key] : undefined);

const assertAdminEndpoint = (url: string | undefined, label: string) => {
  if (!url) {
    throw new ApiError(`${label} endpoint not configured. Check EXPO_PUBLIC_API_BASE_URL / expo.extra.apiBaseUrl to derive site base.`);
  }
};

const postForm = async <T>(url: string, payload: Record<string, string | number | undefined>): Promise<T> => {
  const body = new URLSearchParams();
  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined) return;
    body.append(key, String(value));
  });

  const response = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: body.toString(),
  });

  const text = await response.text();
  if (!response.ok) {
    throw new ApiError(text || 'Request failed', response.status);
  }

  try {
    // admin-ajax returns JSON
    return JSON.parse(text) as T;
  } catch {
    // admin-post may redirect; fallback to text
    return {message: text} as unknown as T;
  }
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
  (() => {
    assertAdminEndpoint(adminAjaxUrl, 'Deposit');
    const nonce = getEnv('EXPO_PUBLIC_WSI_DEPOSIT_NONCE');
    if (!nonce) {
      throw new ApiError('Deposit nonce missing. Set EXPO_PUBLIC_WSI_DEPOSIT_NONCE from the WordPress deposit page source.');
    }

    // Match WP form keys (wsi_submit_deposit)
    const payment_type = payload.method?.toLowerCase().includes('usdt') ? 'crypto' : 'naira';
    return postForm<{success?: boolean; data?: any; message?: string}>(adminAjaxUrl, {
      action: 'wsi_submit_deposit',
      _wpnonce: nonce,
      is_ajax: '1',
      amount: payload.amount,
      payment_type,
      amount_usd: payload.amount,
      note: payload.note,
      token,
    });
  })();

export const submitWithdrawal = (payload: {amount: string; destination: string; note?: string}, token?: string) =>
  (() => {
    assertAdminEndpoint(adminAjaxUrl, 'Withdrawal');
    const nonce = getEnv('EXPO_PUBLIC_WSI_WITHDRAW_NONCE');
    if (!nonce) {
      throw new ApiError('Withdrawal nonce missing. Set EXPO_PUBLIC_WSI_WITHDRAW_NONCE from the WordPress withdrawal page source.');
    }
    const defaultMethod = getEnv('EXPO_PUBLIC_WSI_DEFAULT_WITHDRAW_METHOD') || 'USDT-TRC20';
    return postForm<{success?: boolean; data?: any; message?: string}>(adminAjaxUrl, {
      action: 'wsi_submit_withdraw',
      _wpnonce: nonce,
      is_ajax: '1',
      amount: payload.amount,
      account_details: payload.destination,
      crypto_type: defaultMethod,
      note: payload.note,
      token,
    });
  })();

export const submitReinvest = (payload: {amount: string; note?: string}, token?: string) =>
  (() => {
    assertAdminEndpoint(adminPostUrl, 'Reinvest');
    const nonce = getEnv('EXPO_PUBLIC_WSI_REINVEST_NONCE');
    if (!nonce) {
      throw new ApiError('Reinvest nonce missing. Set EXPO_PUBLIC_WSI_REINVEST_NONCE from the WordPress reinvest page source.');
    }
    return postForm<{message?: string}>(adminPostUrl, {
      action: 'wsi_submit_reinvest',
      _wpnonce: nonce,
      amount: payload.amount,
      note: payload.note,
      token,
    });
  })();

export const submitBuyStock = (payload: {stockId?: string; units?: string; amount?: string}, token?: string) =>
  (() => {
    assertAdminEndpoint(adminPostUrl, 'Buy stock');
    const nonce = getEnv('EXPO_PUBLIC_WSI_BUY_STOCK_NONCE');
    if (!nonce) {
      throw new ApiError('Buy Stock nonce missing. Set EXPO_PUBLIC_WSI_BUY_STOCK_NONCE from the WordPress stocks page source.');
    }
    return postForm<{message?: string}>(adminPostUrl, {
      action: 'wsi_buy_stock',
      _wpnonce: nonce,
      stock_id: payload.stockId,
      units: payload.units,
      amount: payload.amount,
      token,
    });
  })();
