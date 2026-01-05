import React from 'react';
import {ActivityIndicator, View} from 'react-native';
import {NavigationContainer} from '@react-navigation/native';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {Ionicons} from '@expo/vector-icons';
import DashboardScreen from '../screens/DashboardScreen';
import StocksScreen from '../screens/StocksScreen';
import HoldingsScreen from '../screens/HoldingsScreen';
import WalletScreen from '../screens/WalletScreen';
import ActivityScreen from '../screens/ActivityScreen';
import SettingsScreen from '../screens/SettingsScreen';
import LoginScreen from '../screens/auth/LoginScreen';
import SignupScreen from '../screens/auth/SignupScreen';
import ForgotPasswordScreen from '../screens/auth/ForgotPasswordScreen';
import DepositScreen from '../screens/DepositScreen';
import WithdrawScreen from '../screens/WithdrawScreen';
import ReinvestScreen from '../screens/ReinvestScreen';
import BuyStockScreen from '../screens/BuyStockScreen';
import TransactionDetailScreen from '../screens/TransactionDetailScreen';
import UserSettingsScreen from '../screens/UserSettingsScreen';
import {useSession} from '../hooks/useSession';
import {useTheme} from '../theme';

const AuthStack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();
const MainStack = createNativeStackNavigator();

const BottomTabs = () => {
  const theme = useTheme();
  return (
    <Tab.Navigator
      screenOptions={({route}) => ({
        headerShown: false,
        tabBarStyle: {
          backgroundColor: theme.palette.surface,
          borderTopColor: theme.palette.border,
          height: 70,
          paddingBottom: 12,
          paddingTop: 8,
        },
        tabBarActiveTintColor: theme.palette.accent,
        tabBarInactiveTintColor: theme.palette.muted,
        tabBarIcon: ({color, size, focused}) => {
          const nameMap: Record<string, keyof typeof Ionicons.glyphMap> = {
            Dashboard: focused ? 'home' : 'home-outline',
            Stocks: focused ? 'stats-chart' : 'stats-chart-outline',
            Holdings: focused ? 'pie-chart' : 'pie-chart-outline',
            Wallet: focused ? 'wallet' : 'wallet-outline',
            Activity: focused ? 'time' : 'time-outline',
            Settings: focused ? 'settings' : 'settings-outline',
          };
          return <Ionicons name={nameMap[route.name]} size={size} color={color} />;
        },
      })}
    >
      <Tab.Screen name="Dashboard" component={DashboardScreen} />
      <Tab.Screen name="Stocks" component={StocksScreen} />
      <Tab.Screen name="Holdings" component={HoldingsScreen} />
      <Tab.Screen name="Wallet" component={WalletScreen} />
      <Tab.Screen name="Activity" component={ActivityScreen} />
      <Tab.Screen name="Settings" component={SettingsScreen} />
    </Tab.Navigator>
  );
};

const AuthNavigator = () => (
  <AuthStack.Navigator screenOptions={{headerShown: false}}>
    <AuthStack.Screen name="Login" component={LoginScreen} />
    <AuthStack.Screen name="Signup" component={SignupScreen} />
    <AuthStack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
  </AuthStack.Navigator>
);

const AuthedNavigator = () => {
  const theme = useTheme();
  return (
    <MainStack.Navigator
      screenOptions={{
        headerStyle: {backgroundColor: theme.palette.background},
        headerTintColor: theme.palette.text,
      }}
    >
      <MainStack.Screen name="Tabs" component={BottomTabs} options={{headerShown: false}} />
      <MainStack.Screen name="Deposit" component={DepositScreen} options={{title: 'Deposit'}} />
      <MainStack.Screen name="Withdraw" component={WithdrawScreen} options={{title: 'Withdraw'}} />
      <MainStack.Screen name="Reinvest" component={ReinvestScreen} options={{title: 'Reinvest'}} />
      <MainStack.Screen name="BuyStock" component={BuyStockScreen} options={{title: 'Buy Stock'}} />
      <MainStack.Screen name="TransactionDetail" component={TransactionDetailScreen} options={{title: 'Transaction'}} />
      <MainStack.Screen name="UserSettings" component={UserSettingsScreen} options={{title: 'Personal Information'}} />
    </MainStack.Navigator>
  );
};

const AppNavigator = () => {
  const theme = useTheme();
  const {isAuthenticated, hydrated, status} = useSession();

  if (!hydrated || status === 'hydrating') {
    return (
      <View style={{flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: theme.palette.background}}>
        <ActivityIndicator size="large" color={theme.palette.accent} />
      </View>
    );
  }

  return (
    <NavigationContainer theme={theme.navTheme}>
      {isAuthenticated ? <AuthedNavigator /> : <AuthNavigator />}
    </NavigationContainer>
  );
};

export default AppNavigator;
