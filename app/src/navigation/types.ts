import type {
  CompositeNavigationProp,
  NavigatorScreenParams,
  RouteProp,
} from '@react-navigation/native';
import type {BottomTabNavigationProp} from '@react-navigation/bottom-tabs';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import type {Stock, Transaction} from '../types';

export type AuthStackParamList = {
  Login: undefined;
  Signup: undefined;
  ForgotPassword: undefined;
};

export type TabParamList = {
  Dashboard: undefined;
  Stocks: undefined;
  Holdings: undefined;
  Activity: undefined;
  Settings: undefined;
};

export type MainStackParamList = {
  Tabs: NavigatorScreenParams<TabParamList> | undefined;
  Wallet: undefined;
  Deposit: undefined;
  Withdraw: undefined;
  Reinvest: {amount?: string} | undefined;
  BuyStock: {stock: Stock};
  TransactionDetail: {tx: Transaction};
  UserSettings: undefined;
};

export type AuthNavigationProp<RouteName extends keyof AuthStackParamList> =
  NativeStackNavigationProp<AuthStackParamList, RouteName>;

export type TabNavigationProp<RouteName extends keyof TabParamList> =
  CompositeNavigationProp<
    BottomTabNavigationProp<TabParamList, RouteName>,
    NativeStackNavigationProp<MainStackParamList>
  >;

export type MainStackRouteProp<RouteName extends keyof MainStackParamList> =
  RouteProp<MainStackParamList, RouteName>;

export type MainStackNavigationProp<RouteName extends keyof MainStackParamList> =
  NativeStackNavigationProp<MainStackParamList, RouteName>;
