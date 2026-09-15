import React from 'react';
<<<<<<< HEAD
import {ActivityIndicator, StyleSheet, View} from 'react-native';
=======
import {ActivityIndicator, View} from 'react-native';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
import {NavigationContainer} from '@react-navigation/native';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {Ionicons} from '@expo/vector-icons';
<<<<<<< HEAD
import {useSafeAreaInsets} from 'react-native-safe-area-context';
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
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
<<<<<<< HEAD
import type {AuthStackParamList, MainStackParamList, TabParamList} from './types';

const AuthStack = createNativeStackNavigator<AuthStackParamList>();
const Tab = createBottomTabNavigator<TabParamList>();
const MainStack = createNativeStackNavigator<MainStackParamList>();

type TabIconName = keyof typeof Ionicons.glyphMap;

const tabIcons: Record<keyof TabParamList, {active: TabIconName; inactive: TabIconName}> = {
  Dashboard: {active: 'home', inactive: 'home-outline'},
  Stocks: {active: 'stats-chart', inactive: 'stats-chart-outline'},
  Holdings: {active: 'pie-chart', inactive: 'pie-chart-outline'},
  Activity: {active: 'time', inactive: 'time-outline'},
  Settings: {active: 'settings', inactive: 'settings-outline'},
};

const BottomTabs = () => {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const activeBackground = theme.mode === 'dark' ? '#166534' : theme.palette.primary;

=======

const AuthStack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();
const MainStack = createNativeStackNavigator();

const BottomTabs = () => {
  const theme = useTheme();
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
  return (
    <Tab.Navigator
      screenOptions={({route}) => ({
        headerShown: false,
<<<<<<< HEAD
        tabBarShowLabel: false,
        tabBarHideOnKeyboard: true,
        tabBarActiveTintColor: '#FFFFFF',
        tabBarInactiveTintColor: theme.palette.muted,
        tabBarStyle: [
          styles.tabBar,
          {
            backgroundColor: theme.palette.surface,
            borderColor: theme.palette.border,
            shadowOpacity: theme.mode === 'dark' ? 0.32 : 0.14,
            marginBottom: Math.max(insets.bottom, 12),
          },
        ],
        tabBarItemStyle: styles.tabItem,
        tabBarIcon: ({focused, color}) => (
          <View
            style={[
              styles.tabIcon,
              focused && {
                backgroundColor: activeBackground,
                shadowColor: activeBackground,
              },
            ]}
          >
            <Ionicons
              name={focused ? tabIcons[route.name].active : tabIcons[route.name].inactive}
              size={22}
              color={color}
            />
          </View>
        ),
      })}
    >
      <Tab.Screen name="Dashboard" component={DashboardScreen} options={{tabBarAccessibilityLabel: 'Dashboard'}} />
      <Tab.Screen name="Stocks" component={StocksScreen} options={{tabBarAccessibilityLabel: 'Stocks'}} />
      <Tab.Screen name="Holdings" component={HoldingsScreen} options={{tabBarAccessibilityLabel: 'Holdings'}} />
      <Tab.Screen name="Activity" component={ActivityScreen} options={{tabBarAccessibilityLabel: 'Activity'}} />
      <Tab.Screen name="Settings" component={SettingsScreen} options={{tabBarAccessibilityLabel: 'Settings'}} />
=======
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
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
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
<<<<<<< HEAD
      <MainStack.Screen name="Wallet" component={WalletScreen} options={{title: 'Wallet'}} />
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
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

<<<<<<< HEAD
const styles = StyleSheet.create({
  tabBar: {
    alignSelf: 'center',
    width: '92%',
    maxWidth: 480,
    height: 64,
    paddingTop: 6,
    paddingBottom: 6,
    borderTopWidth: 0,
    borderWidth: 1,
    borderRadius: 24,
    shadowColor: '#0F172A',
    shadowOffset: {width: 0, height: 8},
    shadowRadius: 18,
    elevation: 12,
  },
  tabItem: {
    borderRadius: 18,
    paddingVertical: 4,
  },
  tabIcon: {
    width: 44,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    shadowOffset: {width: 0, height: 4},
    shadowOpacity: 0.2,
    shadowRadius: 8,
  },
});

=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
export default AppNavigator;
