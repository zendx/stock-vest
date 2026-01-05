import Constants from 'expo-constants';

export class ApiError extends Error {
  status?: number;
  constructor(message: string, status?: number) {
    super(message);
    this.status = status;
  }
}

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
export const adminAjaxUrl = siteBaseUrl ? `${siteBaseUrl}/wp-admin/admin-ajax.php` : '';
export const adminPostUrl = siteBaseUrl ? `${siteBaseUrl}/wp-admin/admin-post.php` : '';

const buildUrl = (path: string) => {
  if (!apiBaseUrl) {
    throw new ApiError('API base URL is not configured. Set EXPO_PUBLIC_API_BASE_URL or expo.extra.apiBaseUrl.');
  }
  const normalized = path.startsWith('/') ? path : `/${path}`;
  return `${apiBaseUrl}${normalized}`;
};

type RequestOptions = RequestInit & {
  token?: string;
};

export const apiRequest = async <T>(path: string, options: RequestOptions = {}): Promise<T> => {
  const url = buildUrl(path);
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string> | undefined),
    ...(options.token ? {Authorization: `Bearer ${options.token}`} : {}),
  };

  const response = await fetch(url, {...options, headers});
  const body = await response.text();

  if (!response.ok) {
    const message = body || response.statusText;
    throw new ApiError(message, response.status);
  }

  if (!body) return {} as T;

  try {
    return JSON.parse(body) as T;
  } catch (err) {
    throw new ApiError('Failed to parse response JSON', response.status);
  }
};
