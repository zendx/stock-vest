import React, {useEffect, useMemo, useState} from 'react';
import {Alert, ScrollView, StyleSheet, TextInput, View, Switch} from 'react-native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Surface} from '../components/Surface';
import {Typography} from '../components/Typography';
import {PrimaryButton} from '../components/PrimaryButton';
import {useTheme} from '../theme';
import {useUserProfile} from '../hooks/useUserProfile';

const sections = [
  {
    title: 'Contact',
    fields: [
      {key: 'firstName', label: 'First name'},
      {key: 'lastName', label: 'Last name'},
      {key: 'phone', label: 'Phone'},
      {key: 'birthDate', label: 'Birth date'},
    ],
  },
  {
    title: 'Address',
    fields: [
      {key: 'address1', label: 'Address line 1'},
      {key: 'address2', label: 'Address line 2'},
      {key: 'street', label: 'Street'},
      {key: 'landmark', label: 'Landmark'},
      {key: 'city', label: 'City'},
      {key: 'state', label: 'State'},
      {key: 'country', label: 'Country'},
      {key: 'zip', label: 'ZIP'},
    ],
  },
];

const UserSettingsScreen = () => {
  const theme = useTheme();
  const {query, mutation} = useUserProfile();
  const profile = query.data;
  const [form, setForm] = useState<Record<string, any>>({});

  useEffect(() => {
    if (profile) {
      const next: Record<string, any> = {smartFarming: profile.smartFarming || false};
      sections.forEach((section) =>
        section.fields.forEach((f) => {
          next[f.key] = (profile as any)[f.key] || '';
        }),
      );
      setForm(next);
    }
  }, [profile]);

  const dirty = useMemo(() => {
    if (!profile) return false;
    const fieldDirty = sections.some((section) =>
      section.fields.some((f) => (form[f.key] || '') !== ((profile as any)[f.key] || '')),
    );
    const smartDirty = (form.smartFarming === true) !== Boolean(profile.smartFarming);
    return fieldDirty || smartDirty;
  }, [form, profile]);

  const updateField = (key: string, value: string) => setForm({...form, [key]: value});

  const handleSave = () => {
    if (!dirty) {
      Alert.alert('No changes', 'Update a field before saving.');
      return;
    }
    const payload = {
      ...form,
      smart_farming: form.smartFarming === true || form.smartFarming === 'yes' ? 'yes' : 'no',
    };
    mutation.mutate(payload, {
      onSuccess: () => {
        Alert.alert('Saved', 'Profile updated successfully.');
      },
      onError: (err: any) => {
        Alert.alert('Update failed', err?.message || 'Unable to update profile right now.');
      },
    });
  };

  return (
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Personal Information
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          Keep profile details synced with the plugin.
        </Typography>
      </View>

      <ScrollView contentContainerStyle={{paddingBottom: 20}}>
        <Surface style={{padding: 14, marginBottom: 12}}>
          <Typography variant="subtitle" weight="medium">
            Profile Details
          </Typography>
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 6}}>
            Mirrors user-settings.php inside the app. Saved via the WordPress REST bridge.
          </Typography>
        </Surface>

        {sections.map((section) => (
          <Surface key={section.title} style={{padding: 14, marginBottom: 12}}>
            <Typography weight="medium" style={{marginBottom: 10}}>
              {section.title}
            </Typography>
            <View style={{gap: 10}}>
              {section.fields.map((field) => (
                <View key={field.key} style={{gap: 6}}>
                  <Typography variant="caption" style={{color: theme.palette.muted}}>
                    {field.label}
                  </Typography>
                  <TextInput
                    placeholder={field.label}
                    placeholderTextColor={theme.palette.muted}
                    value={form[field.key] || ''}
                    onChangeText={(text) => updateField(field.key, text)}
                    style={[
                      styles.input,
                      {
                        borderColor: theme.palette.border,
                        backgroundColor: theme.palette.surface,
                        color: theme.palette.text,
                      },
                    ]}
                  />
                </View>
              ))}
            </View>
          </Surface>
        ))}

        <Surface style={{padding: 14}}>
          <PrimaryButton
            label={mutation.isPending ? 'Saving...' : 'Save changes'}
            fullWidth
            onPress={mutation.isPending ? undefined : handleSave}
            disabled={mutation.isPending || !dirty}
          />
          {query.isLoading ? (
            <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 10}}>
              Loading profile...
            </Typography>
          ) : null}
          <View style={[styles.toggleRow, {marginTop: 16}]}>
            <View style={styles.rowLeft}>
              <View style={[styles.iconWrap, {backgroundColor: '#E5F6EC'}]}>
                <Ionicons name="leaf-outline" size={18} color={theme.palette.primary} />
              </View>
              <View>
                <Typography weight="medium">Smart Farming</Typography>
                <Typography variant="caption" style={{color: theme.palette.muted}}>
                  {form.smartFarming === 'yes' || form.smartFarming === true ? 'Activated' : 'Disabled'}
                </Typography>
              </View>
            </View>
            <Switch
              value={form.smartFarming === 'yes' || form.smartFarming === true}
              onValueChange={(val) => setForm({...form, smartFarming: val ? 'yes' : 'no'})}
              thumbColor={theme.palette.surface}
              trackColor={{true: theme.palette.primary, false: theme.palette.border}}
            />
          </View>
        </Surface>

        <Surface muted style={{marginTop: 12, padding: 14}}>
          <View style={styles.row}>
            <View style={styles.rowLeft}>
              <View style={[styles.iconWrap, {backgroundColor: '#E5F6EC'}]}>
                <Ionicons name="person-outline" size={18} color={theme.palette.primary} />
              </View>
              <View>
                <Typography weight="medium">Secure and synced</Typography>
                <Typography variant="caption" style={{color: theme.palette.muted}}>
                  Updates persist via REST, matching the WordPress profile form.
                </Typography>
              </View>
            </View>
            <Ionicons name="chevron-forward" size={18} color={theme.palette.muted} />
          </View>
        </Surface>
      </ScrollView>
    </Screen>
  );
};

const styles = StyleSheet.create({
  hero: {
    padding: 16,
    borderRadius: 18,
    marginBottom: 12,
  },
  input: {
    borderWidth: 1,
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 12,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
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
  toggleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
});

export default UserSettingsScreen;
