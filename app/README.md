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
