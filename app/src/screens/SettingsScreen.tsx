<<<<<<< HEAD
import React from 'react';
import {Linking, Switch, View, StyleSheet, Pressable} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Ionicons} from '@expo/vector-icons';
import Constants from 'expo-constants';
import {Screen} from '../components/Screen';
import {Typography} from '../components/Typography';
import {Surface} from '../components/Surface';
import {useSession} from '../hooks/useSession';
import {useTheme} from '../theme';
import type {TabNavigationProp} from '../navigation/types';
import {openWebFlow} from '../lib/openWebFlow';
=======
import React, {useState} from 'react';
import {Switch, View, StyleSheet, Pressable} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Typography} from '../components/Typography';
import {Surface} from '../components/Surface';
import {PrimaryButton} from '../components/PrimaryButton';
import {useSession} from '../hooks/useSession';
import {useTheme} from '../theme';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

const SettingsScreen = () => {
  const {user, logout, status} = useSession();
  const theme = useTheme();
<<<<<<< HEAD
  const darkMode = theme.mode === 'dark';
  const navigation = useNavigation<TabNavigationProp<'Settings'>>();
=======
  const [notifications, setNotifications] = useState(false);
  const [darkMode, setDarkMode] = useState(theme.mode === 'dark');
  const navigation = useNavigation();
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

  const renderRow = (icon: string, label: string, sub?: string, action?: React.ReactNode, onPress?: () => void) => (
    <Pressable onPress={onPress}>
      <Surface style={styles.row}>
        <View style={styles.rowLeft}>
          <View style={[styles.iconWrap, {backgroundColor: theme.palette.accentSoft}]}>
            <Ionicons name={icon as any} size={18} color={theme.palette.primary} />
          </View>
          <View>
            <Typography weight="medium">{label}</Typography>
            {sub ? (
              <Typography variant="caption" style={{color: theme.palette.muted}}>
                {sub}
              </Typography>
            ) : null}
          </View>
        </View>
        {action || <Ionicons name="chevron-forward" size={18} color={theme.palette.muted} />}
      </Surface>
    </Pressable>
  );

  return (
<<<<<<< HEAD
    <Screen bottomInset={false}>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Open personal information"
        onPress={() => navigation.navigate('UserSettings')}
        style={[styles.hero, {backgroundColor: theme.palette.primary}]}
      >
=======
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Settings
        </Typography>
        <View style={{marginTop: 12}}>
          <Typography weight="medium" style={{color: '#fff'}}>
<<<<<<< HEAD
            {user?.name || 'Investor'}
          </Typography>
          <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 4}}>
            {user?.email || 'No email available'}
=======
            {user?.name || 'John Doe'}
          </Typography>
          <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 4}}>
            {user?.email || 'john.doe@example.com'}
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
          </Typography>
          <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 4}}>
            View Profile
          </Typography>
        </View>
<<<<<<< HEAD
      </Pressable>
=======
      </View>
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

      <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 14, marginBottom: 6}}>
        Account
      </Typography>
      {renderRow('person-outline', 'Personal Information', 'Update your details', undefined, () =>
<<<<<<< HEAD
        navigation.navigate('UserSettings'),
      )}
      {renderRow('shield-checkmark-outline', 'Security', 'Reset your password', undefined, () => {
        void openWebFlow('/wsi/forgot-password/', 'Password reset');
      })}
=======
        navigation.navigate('UserSettings' as never),
      )}
      {renderRow('shield-checkmark-outline', 'Security', 'Password & 2FA')}
      {renderRow('lock-closed-outline', 'Privacy', 'Data & permissions')}
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

      <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 16, marginBottom: 6}}>
        Preferences
      </Typography>
      {renderRow(
<<<<<<< HEAD
=======
        'notifications-outline',
        'Notifications',
        'Push notifications',
        <Switch
          value={notifications}
          onValueChange={setNotifications}
          thumbColor={notifications ? theme.palette.surface : '#fff'}
          trackColor={{true: theme.palette.primary, false: theme.palette.border}}
        />,
      )}
      {renderRow(
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        'moon-outline',
        'Dark Mode',
        'Theme preference',
        <Switch
          value={darkMode}
          onValueChange={(val) => {
<<<<<<< HEAD
=======
            setDarkMode(val);
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
            theme.setMode(val ? 'dark' : 'light');
          }}
          thumbColor={darkMode ? theme.palette.surface : '#fff'}
          trackColor={{true: theme.palette.primary, false: theme.palette.border}}
        />,
      )}
<<<<<<< HEAD
=======
      {renderRow('globe-outline', 'Language', 'English (US)')}
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

      <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 16, marginBottom: 6}}>
        Support
      </Typography>
<<<<<<< HEAD
      {renderRow('help-circle-outline', 'Contact Support', 'cofcocapital@gmail.com', undefined, () => {
        void Linking.openURL('mailto:cofcocapital@gmail.com?subject=COFCO%20Capital%20iOS%20Support');
      })}
      <Pressable accessibilityRole="button" accessibilityLabel="Log out" onPress={() => void logout()}>
        <Surface style={styles.row}>
          <View style={styles.rowLeft}>
            <View style={[styles.iconWrap, {backgroundColor: '#FDECEC'}]}>
              <Ionicons name="log-out-outline" size={18} color={theme.palette.danger} />
            </View>
            <View>
              <Typography weight="medium" style={{color: theme.palette.danger}}>
                Log Out
              </Typography>
              <Typography variant="caption" style={{color: theme.palette.muted}}>
                Sign out of your account
              </Typography>
            </View>
          </View>
          <Typography variant="caption" style={{color: theme.palette.danger}}>
            {status === 'loading' ? 'Signing out...' : 'Sign out'}
          </Typography>
        </Surface>
      </Pressable>

      <View style={{alignItems: 'center', marginTop: 16}}>
        <Typography variant="caption" style={{color: theme.palette.muted}}>
          Version {Constants.expoConfig?.version || '1.0.0'}
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 2}}>
          © {new Date().getFullYear()} COFCO Capital
=======
      {renderRow('help-circle-outline', 'Help Center', 'FAQs & Support')}
      <Surface style={styles.row}>
        <View style={styles.rowLeft}>
          <View style={[styles.iconWrap, {backgroundColor: '#FDECEC'}]}>
            <Ionicons name="log-out-outline" size={18} color={theme.palette.danger} />
          </View>
          <View>
            <Typography weight="medium" style={{color: theme.palette.danger}}>
              Log Out
            </Typography>
            <Typography variant="caption" style={{color: theme.palette.muted}}>
              Sign out of your account
            </Typography>
          </View>
        </View>
        <Pressable onPress={logout}>
          <Typography variant="caption" style={{color: theme.palette.danger}}>
            {status === 'loading' ? 'Signing out...' : 'Sign out'}
          </Typography>
        </Pressable>
      </Surface>

      <View style={{alignItems: 'center', marginTop: 16}}>
        <Typography variant="caption" style={{color: theme.palette.muted}}>
          Version 1.0.0
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 2}}>
          © 2026 COFCO Capital
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        </Typography>
      </View>
    </Screen>
  );
};

const styles = StyleSheet.create({
  hero: {
    padding: 16,
    borderRadius: 18,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 14,
    paddingHorizontal: 12,
    borderRadius: 14,
    borderWidth: 1,
    marginBottom: 8,
  },
  rowLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  iconWrap: {
    width: 34,
    height: 34,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default SettingsScreen;
