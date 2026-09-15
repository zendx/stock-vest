<<<<<<< HEAD
# COFCO Capital for iOS

Expo SDK 54 / React Native client for the COFCO Capital WordPress investment portal.

## Included

- Native login, secure token storage/session restoration, and invite-code registration handoff
- Dashboard, stocks, holdings, wallet, activity, and account screens
- Deposit, withdrawal, reinvestment, stock order, transaction detail, and profile screens
- Light/dark theme support and iPhone-safe layouts
- EAS profiles for an iOS Simulator, internal preview, and App Store production builds

## Requirements

- Node.js 20.19 or newer
- An Expo account with access to the EAS project in `app.json`
- An Apple Developer account for device, TestFlight, or App Store signing

Install and validate:

```powershell
npm install
npm run validate
```

Run with Expo Go:

```powershell
npm start
```

## iOS builds from Windows

iOS binaries cannot be compiled locally on Windows. The scripts below use EAS cloud builders:

```powershell
# Standalone .app for an iOS Simulator (no Apple signing required)
npm run build:ios:simulator

# Internal build for registered iPhones
npm run build:ios:preview

# Signed App Store / TestFlight build
npm run build:ios
```

The first signed build prompts for Expo and Apple credentials. EAS manages the iOS build number remotely and increments production builds automatically.

## Configuration

Public runtime URLs are in `.env.example`, `app.json`, and the EAS build profiles. Values prefixed with `EXPO_PUBLIC_` are embedded in the binary and must never contain credentials or WordPress nonces.

The app currently targets:

```text
https://cofco.capital/sv/wp-json/wsi/v1
```

The deployed API provides bearer-authenticated reads for the account, balances, stocks, holdings, transactions, and profile. Before financial submissions are released to customers, the WordPress plugin must expose bearer-authenticated REST write routes for deposits, withdrawals, reinvestments, and orders. Its existing browser-only cookie/nonce handlers are not a safe mobile API.

## App Store handoff

Confirm ownership of `com.cofco.capital` and the EAS project ID, then create the production build. After it succeeds, upload the latest artifact with:

```powershell
npx eas-cli submit --platform ios --latest
```
=======
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
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
