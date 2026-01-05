# COFCO Capital mobile app (Expo)

## Setup
- Install deps: `cd app && npm install` (adds Expo, async storage, secure store, Google fonts packages).
- Configure API base URL via env: set `EXPO_PUBLIC_API_BASE_URL=https://your-api.example.com` when running `expo start`/`eas build`. A default placeholder also lives in `app/app.json` under `expo.extra.apiBaseUrl`.
- If you are using the WordPress plugin REST API added here, set the base to `https://<your-site>/wp-json/wsi/v1`.

## Expected backend endpoints
- `POST /auth/login` -> `{ token, user }`
- `POST /auth/signup` -> `{ token, user }`
- `GET /auth/me` -> `user` (used during hydration)
- `GET /balances` -> `{ totalAssets, profit, available, net }`
- `GET /stocks` -> `Array<{ id, name, rate, status, price }>`
- `GET /transactions` -> `Array<{ id, title, amount, date, status }>`
Update the paths/payloads in `app/src/api/*.ts` if your API differs.

## Building
- Update bundle IDs in `app/app.json` (`ios.bundleIdentifier`, `android.package`).
- EAS profiles are in `app/eas.json` with placeholder API URLs; adjust them to match your environments.
- Create/sign in to an Expo account, then run from `app/`: `npx expo login` and `npx eas build --platform android|ios`.

## Fonts/assets
- Fonts load from `@expo-google-fonts/lexend` and `@expo-google-fonts/open-sans` and are preloaded in `App.tsx`.
- App icon/splash use `app/assets/logo.png`.
