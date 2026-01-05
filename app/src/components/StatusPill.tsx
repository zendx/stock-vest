import React from 'react';
import {View, Text, StyleSheet} from 'react-native';
import {useTheme} from '../theme';

type Props = {
  label: string;
  tone?: 'success' | 'warning' | 'danger' | 'info';
};

export const StatusPill = ({label, tone = 'info'}: Props) => {
  const theme = useTheme();
  const colors: Record<typeof tone, {bg: string; text: string}> = {
    success: {bg: '#193524', text: theme.palette.success},
    warning: {bg: '#2F250F', text: theme.palette.warning},
    danger: {bg: '#3B1A1C', text: theme.palette.danger},
    info: {bg: '#0F2435', text: theme.palette.info},
  };
  const palette = colors[tone];
  return (
    <View style={[styles.chip, {backgroundColor: palette.bg}]}>
      <Text style={[styles.text, {color: palette.text}]}>{label}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  chip: {
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  text: {
    fontSize: 12,
    fontWeight: '600',
    letterSpacing: 0.2,
  },
});
