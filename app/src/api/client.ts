import Constants from 'expo-constants';

export class ApiError extends Error {
  status?: number;
  constructor(message: string, status?: number) {
    super(message);
<<<<<<< HEAD
    this.name = 'ApiError';
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
    this.status = status;
  }
}

<<<<<<< HEAD
const REQUEST_TIMEOUT_MS = 15_000;

=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
const rawBaseUrl =
  process.env.EXPO_PUBLIC_API_BASE_URL ||
  (Constants.expoConfig?.extra?.apiBaseUrl as string | undefined) ||
  '';

export const apiBaseUrl = rawBaseUrl.endsWith('/') ? rawBaseUrl.slice(0, -1) : rawBaseUrl;

const computeSiteBase = () => {
  if (!apiBaseUrl) return '';
  try {
    const url = new URL(apiBaseUrl);
    const idx = url.pathname.indexOf('/wp-json');
    const basePath = idx >= 0 ? url.pathname.slice(0, idx) : url.pathname;
    url.pathname = basePath || '/';
    url.search = '';
    url.hash = '';
    const normalized = url.toString().replace(/\/$/, '');
    return normalized;
  } catch {
    return '';
  }
};

export const siteBaseUrl = computeSiteBase();
<<<<<<< HEAD
=======
export const adminAjaxUrl = siteBaseUrl ? `${siteBaseUrl}/wp-admin/admin-ajax.php` : '';
export const adminPostUrl = siteBaseUrl ? `${siteBaseUrl}/wp-admin/admin-post.php` : '';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

const buildUrl = (path: string) => {
  if (!apiBaseUrl) {
    throw new ApiError('API base URL is not configured. Set EXPO_PUBLIC_API_BASE_URL or expo.extra.apiBaseUrl.');
  }
  const normalized = path.startsWith('/') ? path : `/${path}`;
  return `${apiBaseUrl}${normalized}`;
};

type RequestOptions = RequestInit & {
  token?: string;
<<<<<<< HEAD
  timeoutMs?: number;
};

const getErrorMessage = (body: unknown): string | undefined => {
  if (!body || typeof body !== 'object') return undefined;

  const error = body as Record<string, unknown>;
  if (typeof error.message === 'string' && error.message.trim()) return error.message.trim();
  if (typeof error.error === 'string' && error.error.trim()) return error.error.trim();
  if (typeof error.data === 'string' && error.data.trim()) return error.data.trim();
  return getErrorMessage(error.data);
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
};

export const apiRequest = async <T>(path: string, options: RequestOptions = {}): Promise<T> => {
  const url = buildUrl(path);
<<<<<<< HEAD
  const {
    token,
    timeoutMs = REQUEST_TIMEOUT_MS,
    signal: callerSignal,
    headers: suppliedHeaders,
    ...requestOptions
  } = options;
  const headers = {
    'Content-Type': 'application/json',
    ...(suppliedHeaders as Record<string, string> | undefined),
    ...(token ? {Authorization: `Bearer ${token}`} : {}),
  };

  const controller = new AbortController();
  let timedOut = false;
  const abortFromCaller = () => controller.abort();
  if (callerSignal?.aborted) {
    controller.abort();
  } else {
    callerSignal?.addEventListener('abort', abortFromCaller);
  }
  const timeout = setTimeout(() => {
    timedOut = true;
    controller.abort();
  }, timeoutMs);

  let response: Response;
  let body: string;
  try {
    response = await fetch(url, {...requestOptions, headers, signal: controller.signal});
    body = await response.text();
  } catch (err) {
    if (timedOut) throw new ApiError('Request timed out. Please try again.');
    if (callerSignal?.aborted) throw new ApiError('Request cancelled.');
    throw new ApiError(err instanceof Error ? err.message : 'Unable to reach the server.');
  } finally {
    clearTimeout(timeout);
    callerSignal?.removeEventListener('abort', abortFromCaller);
  }

  let json: unknown;
  if (body) {
    try {
      json = JSON.parse(body);
    } catch {
      json = undefined;
    }
  }

  if (!response.ok) {
    const message = getErrorMessage(json) || response.statusText || 'Request failed';
=======
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string> | undefined),
    ...(options.token ? {Authorization: `Bearer ${options.token}`} : {}),
  };

  const response = await fetch(url, {...options, headers});
  const body = await response.text();

  if (!response.ok) {
    const message = body || response.statusText;
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
    throw new ApiError(message, response.status);
  }

  if (!body) return {} as T;
<<<<<<< HEAD
  if (json === undefined) throw new ApiError('Failed to parse response JSON', response.status);
  return json as T;
=======

  try {
    return JSON.parse(body) as T;
  } catch (err) {
    throw new ApiError('Failed to parse response JSON', response.status);
  }
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
};
