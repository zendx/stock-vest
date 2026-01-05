import React from 'react';
import {Pressable, StyleSheet, ViewStyle} from 'react-native';
import {LinearGradient} from 'expo-linear-gradient';
import {Typography} from './Typography';
import {useTheme} from '../theme';

type Props = {
  label: string;
  onPress?: () => void;
  fullWidth?: boolean;
  style?: ViewStyle;
  compact?: boolean;
  disabled?: boolean;
};

export const PrimaryButton = ({label, onPress, fullWidth = false, style, compact = false, disabled = false}: Props) => {
  const theme = useTheme();
  return (
    <Pressable
      onPress={disabled ? undefined : onPress}
      style={[fullWidth && {width: '100%'}, disabled && {opacity: 0.6}, style]}
      android_ripple={{color: '#ffffff22'}}
      disabled={disabled}
    >
      <LinearGradient
        colors={[theme.palette.primary, theme.palette.accent]}
        start={{x: 0, y: 0}}
        end={{x: 1, y: 1}}
        style={[
          styles.button,
          {borderRadius: compact ? theme.radius.md : theme.radius.lg},
          compact && {paddingVertical: 10, paddingHorizontal: 12},
        ]}
      >
        <Typography style={styles.label} weight="bold">
          {label}
        </Typography>
      </LinearGradient>
    </Pressable>
  );
};

const styles = StyleSheet.create({
  button: {
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: {
    color: '#fff',
    letterSpacing: 0.4,
  },
});
