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

const SettingsScreen = () => {
  const {user, logout, status} = useSession();
  const theme = useTheme();
  const [notifications, setNotifications] = useState(false);
  const [darkMode, setDarkMode] = useState(theme.mode === 'dark');
  const navigation = useNavigation();

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
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Settings
        </Typography>
        <View style={{marginTop: 12}}>
          <Typography weight="medium" style={{color: '#fff'}}>
            {user?.name || 'John Doe'}
          </Typography>
          <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 4}}>
            {user?.email || 'john.doe@example.com'}
          </Typography>
          <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 4}}>
            View Profile
          </Typography>
        </View>
      </View>

      <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 14, marginBottom: 6}}>
        Account
      </Typography>
      {renderRow('person-outline', 'Personal Information', 'Update your details', undefined, () =>
        navigation.navigate('UserSettings' as never),
      )}
      {renderRow('shield-checkmark-outline', 'Security', 'Password & 2FA')}
      {renderRow('lock-closed-outline', 'Privacy', 'Data & permissions')}

      <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 16, marginBottom: 6}}>
        Preferences
      </Typography>
      {renderRow(
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
        'moon-outline',
        'Dark Mode',
        'Theme preference',
        <Switch
          value={darkMode}
          onValueChange={(val) => {
            setDarkMode(val);
            theme.setMode(val ? 'dark' : 'light');
          }}
          thumbColor={darkMode ? theme.palette.surface : '#fff'}
          trackColor={{true: theme.palette.primary, false: theme.palette.border}}
        />,
      )}
      {renderRow('globe-outline', 'Language', 'English (US)')}

      <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 16, marginBottom: 6}}>
        Support
      </Typography>
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
